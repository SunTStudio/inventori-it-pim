# Inventaris PC Kantor: Agent (.exe) + Laravel + Tombol Sinkron

Panduan lengkap membuat sistem inventaris PC kantor:

- **Agent** (Python, dijadikan `.exe`) berjalan di belakang layar di setiap PC client, mengumpulkan nama PC, RAM, disk, CPU, IP, dll.
- **Laravel** (server lokal) menerima data, menyimpannya, dan menampilkan dashboard.
- **Tombol Sinkron** di dashboard untuk meminta data terbaru dari PC client kapan saja.

---

## 1. Cara kerja

Server tidak bisa "menyuruh" PC client secara langsung karena firewall Windows biasanya memblokir koneksi masuk. Maka agent yang **menanyakan ke server** secara berkala (polling):

```
Dashboard Laravel                          Agent di PC client
─────────────────                          ──────────────────
klik [Sinkron]
 └─ set sync_requested_at = now()
                                           tiap 30 detik:
                          ◄──────────────  POST /api/poll   (cek-in)
 jawab {"sync": true}     ──────────────►
                                           kumpulkan data PC
                          ◄──────────────  POST /api/report (data lengkap)
 simpan data,
 hapus sync_requested_at
```

Hasilnya:

- Klik **Sinkron** → data PC online ter-update dalam **±30 detik**.
- PC yang sedang offline/mati tetap ditandai *Menunggu sinkron* dan otomatis mengirim data begitu menyala.
- Tanpa klik apa pun, agent tetap lapor rutin tiap **15 menit**.
- Status **Online/Offline** berdasarkan cek-in terakhir (online jika cek-in kurang dari 3 menit lalu).
- PC baru yang belum terdaftar otomatis mendaftar sendiri saat pertama kali cek-in.
- Client tidak perlu membuka port apa pun. Hanya perlu bisa mengakses server Laravel.

---

## 2. Prasyarat

| Di mana | Kebutuhan |
|---|---|
| Server | PHP 8.2+, Composer, Laravel 11/12, database (MySQL/SQLite) |
| PC client | Windows 10/11 |
| PC untuk build .exe | Python 3.10+ (`pip install psutil requests pyinstaller`) |
| Jaringan | PC client bisa mengakses IP:port server Laravel |

---

## 3. Bagian Laravel (server)

### 3.1 Aktifkan route API

Laravel 11/12 belum mengaktifkan `routes/api.php` secara default:

```
php artisan install:api
```

(Pilih tanpa Sanctum kalau ditanya, karena kita memakai API key sederhana.)

### 3.2 Token rahasia

Buat token acak, misalnya dengan `php -r "echo bin2hex(random_bytes(24));"`.

`.env`:

```
AGENT_API_KEY=isi-token-acak-kamu
```

`config/services.php` (tambahkan di dalam array):

```php
'agent' => ['key' => env('AGENT_API_KEY')],
```

### 3.3 Model dan migration

```
php artisan make:model Pc -m
```

`database/migrations/xxxx_xx_xx_xxxxxx_create_pcs_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pcs', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();            // UUID motherboard
            $table->string('hostname')->index();
            $table->string('ip')->nullable();
            $table->string('mac')->nullable();
            $table->string('login_user')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial')->nullable();
            $table->string('os')->nullable();
            $table->string('cpu')->nullable();
            $table->unsignedSmallInteger('cpu_cores')->nullable();
            $table->unsignedSmallInteger('cpu_threads')->nullable();
            $table->decimal('ram_gb', 6, 1)->nullable();
            $table->decimal('uptime_h', 8, 1)->nullable();
            $table->json('disks')->nullable();           // {physical:[...], partitions:[...]}
            $table->timestamp('last_report_at')->nullable();     // terakhir kirim data lengkap
            $table->timestamp('last_poll_at')->nullable();       // terakhir agent cek-in
            $table->timestamp('sync_requested_at')->nullable();  // diisi saat tombol Sinkron diklik
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pcs');
    }
};
```

> Kalau sebelumnya sudah menjalankan migration versi lama (kolom `last_seen_at`), jalankan `php artisan migrate:fresh` (menghapus data, aman kalau masih tahap uji coba).

`app/Models/Pc.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pc extends Model
{
    protected $guarded = [];

    protected $casts = [
        'disks'             => 'array',
        'last_report_at'    => 'datetime',
        'last_poll_at'      => 'datetime',
        'sync_requested_at' => 'datetime',
    ];

    // Online jika agent cek-in dalam 3 menit terakhir
    public function getIsOnlineAttribute(): bool
    {
        return $this->last_poll_at && $this->last_poll_at->gt(now()->subMinutes(3));
    }

    // Ada permintaan sinkron yang belum dipenuhi agent
    public function getSyncPendingAttribute(): bool
    {
        return $this->sync_requested_at !== null;
    }
}
```

### 3.4 Middleware API key

```
php artisan make:middleware AgentKey
```

`app/Http/Middleware/AgentKey.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AgentKey
{
    public function handle(Request $request, Closure $next)
    {
        $key = (string) config('services.agent.key');

        if ($key === '' || ! hash_equals($key, (string) $request->header('X-API-Key'))) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        return $next($request);
    }
}
```

Daftarkan alias di `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'agent.key' => \App\Http\Middleware\AgentKey::class,
    ]);
})
```

### 3.5 Controller

```
php artisan make:controller PcReportController
php artisan make:controller PcController
```

`app/Http/Controllers/PcReportController.php` (menerima data dari agent):

```php
<?php

namespace App\Http\Controllers;

use App\Models\Pc;
use Illuminate\Http\Request;

class PcReportController extends Controller
{
    /** Agent mengirim data lengkap */
    public function store(Request $request)
    {
        $d = $request->validate([
            'uid'            => 'required|string|max:100',
            'hostname'       => 'required|string|max:100',
            'ip'             => 'nullable|string|max:50',
            'mac'            => 'nullable|string|max:50',
            'login_user'     => 'nullable|string|max:150',
            'manufacturer'   => 'nullable|string|max:100',
            'model'          => 'nullable|string|max:100',
            'serial'         => 'nullable|string|max:100',
            'os'             => 'nullable|string|max:150',
            'cpu'            => 'nullable|string|max:150',
            'cpu_cores'      => 'nullable|integer',
            'cpu_threads'    => 'nullable|integer',
            'ram_gb'         => 'nullable|numeric',
            'uptime_h'       => 'nullable|numeric',
            'physical_disks' => 'nullable|array',
            'partitions'     => 'nullable|array',
        ]);

        $disks = [
            'physical'   => $d['physical_disks'] ?? [],
            'partitions' => $d['partitions'] ?? [],
        ];
        unset($d['physical_disks'], $d['partitions']);

        Pc::updateOrCreate(
            ['uid' => $d['uid']],
            $d + [
                'disks'             => $disks,
                'last_report_at'    => now(),
                'last_poll_at'      => now(),
                'sync_requested_at' => null,   // permintaan sinkron terpenuhi
            ]
        );

        return response()->json(['ok' => true]);
    }

    /** Agent cek-in: ada permintaan sinkron atau tidak? */
    public function poll(Request $request)
    {
        $data = $request->validate([
            'uid'      => 'required|string|max:100',
            'hostname' => 'nullable|string|max:100',
        ]);

        $pc = Pc::where('uid', $data['uid'])->first();

        // PC baru, belum terdaftar -> minta kirim data lengkap
        if (! $pc) {
            return response()->json(['sync' => true]);
        }

        $pc->updateQuietly(['last_poll_at' => now()]);

        return response()->json(['sync' => $pc->sync_requested_at !== null]);
    }
}
```

`app/Http/Controllers/PcController.php` (dashboard dan tombol sinkron):

```php
<?php

namespace App\Http\Controllers;

use App\Models\Pc;

class PcController extends Controller
{
    public function index()
    {
        return view('pcs.index', ['pcs' => Pc::orderBy('hostname')->get()]);
    }

    /** Tombol "Sinkronkan semua" */
    public function syncAll()
    {
        Pc::query()->update(['sync_requested_at' => now()]);

        return back()->with('status',
            'Permintaan sinkron dikirim ke semua PC. PC yang online akan memperbarui data dalam ±30 detik.');
    }

    /** Tombol "Sinkron" per PC */
    public function sync(Pc $pc)
    {
        $pc->update(['sync_requested_at' => now()]);

        return back()->with('status', "Permintaan sinkron untuk {$pc->hostname} dikirim.");
    }
}
```

### 3.6 Route

`routes/api.php`:

```php
<?php

use App\Http\Controllers\PcReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('agent.key')->group(function () {
    Route::post('/report', [PcReportController::class, 'store']);
    Route::post('/poll', [PcReportController::class, 'poll']);
});
```

`routes/web.php`:

```php
use App\Http\Controllers\PcController;

Route::get('/pcs', [PcController::class, 'index'])->name('pcs.index');
Route::post('/pcs/sync', [PcController::class, 'syncAll'])->name('pcs.sync-all');
Route::post('/pcs/{pc}/sync', [PcController::class, 'sync'])->name('pcs.sync');
```

### 3.7 Dashboard (Blade)

`resources/views/pcs/index.blade.php`:

```blade
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- refresh lebih cepat (10 dtk) kalau ada sinkron yang sedang menunggu --}}
    <meta http-equiv="refresh" content="{{ $pcs->contains('sync_pending', true) ? 10 : 60 }}">
    <title>Inventaris PC Kantor</title>
    <style>
        body{font-family:system-ui,Arial,sans-serif;margin:0;background:#f4f5f7;color:#222}
        header{background:#1f2937;color:#fff;padding:16px 24px;display:flex;justify-content:space-between;align-items:center}
        header h1{margin:0;font-size:20px}
        .wrap{padding:20px 24px}
        .flash{background:#dcfce7;border:1px solid #86efac;color:#166534;padding:10px 14px;border-radius:6px;margin-bottom:14px}
        .stats{display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap}
        .card{background:#fff;border-radius:8px;padding:12px 18px;box-shadow:0 1px 3px #0002}
        .card b{display:block;font-size:22px}
        input{padding:8px 10px;width:280px;border:1px solid #ccc;border-radius:6px;margin-bottom:12px}
        .scroll{overflow-x:auto}
        table{border-collapse:collapse;width:100%;background:#fff;box-shadow:0 1px 3px #0002}
        th,td{padding:9px 12px;text-align:left;border-bottom:1px solid #eee;font-size:14px;vertical-align:top}
        th{background:#f0f2f5;white-space:nowrap}
        .dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px}
        .on{background:#16a34a}.off{background:#dc2626}
        .badge{display:inline-block;background:#fef3c7;color:#92400e;border-radius:4px;padding:1px 6px;font-size:12px;margin-top:4px}
        .low{color:#dc2626;font-weight:600}
        .btn{background:#2563eb;color:#fff;border:0;border-radius:6px;padding:8px 14px;cursor:pointer;font-size:14px}
        .btn:disabled{background:#9ca3af;cursor:not-allowed}
        .btn.sm{padding:4px 10px;font-size:12px}
        small{color:#666}
    </style>
</head>
<body>
<header>
    <h1>Inventaris PC Kantor</h1>
    <form method="POST" action="{{ route('pcs.sync-all') }}">
        @csrf
        <button class="btn" onclick="return confirm('Minta semua PC mengirim data terbaru?')">⟳ Sinkronkan semua</button>
    </form>
</header>

<div class="wrap">
    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <div class="stats">
        <div class="card"><b>{{ $pcs->count() }}</b>Total PC</div>
        <div class="card"><b>{{ $pcs->where('is_online', true)->count() }}</b>Online</div>
        <div class="card"><b>{{ $pcs->where('is_online', false)->count() }}</b>Offline</div>
        <div class="card"><b>{{ $pcs->where('sync_pending', true)->count() }}</b>Menunggu sinkron</div>
    </div>

    <input id="q" placeholder="Cari hostname / IP / user / serial..." onkeyup="filterRows()">

    <div class="scroll">
    <table id="t">
        <thead>
        <tr>
            <th>Status</th><th>Hostname</th><th>IP</th><th>User</th><th>Perangkat</th>
            <th>CPU</th><th>RAM</th><th>Disk</th><th>OS</th><th>Uptime</th>
            <th>Data terakhir</th><th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($pcs as $p)
            <tr>
                <td>
                    <span class="dot {{ $p->is_online ? 'on' : 'off' }}"></span>{{ $p->is_online ? 'Online' : 'Offline' }}
                    @if ($p->sync_pending)<br><span class="badge">Menunggu sinkron…</span>@endif
                </td>
                <td><b>{{ $p->hostname }}</b><br><small>{{ $p->mac }}</small></td>
                <td>{{ $p->ip }}</td>
                <td>{{ $p->login_user ?: '-' }}</td>
                <td>{{ $p->manufacturer }} {{ $p->model }}<br><small>SN: {{ $p->serial ?: '-' }}</small></td>
                <td>{{ $p->cpu }}<br><small>{{ $p->cpu_cores }} core / {{ $p->cpu_threads }} thread</small></td>
                <td>{{ $p->ram_gb }} GB</td>
                <td>
                    @foreach ($p->disks['physical'] ?? [] as $d)
                        {{ $d['model'] ?? '-' }} <small>({{ $d['type'] ?? '?' }}, {{ $d['size_gb'] ?? '?' }} GB)</small><br>
                    @endforeach
                    @foreach ($p->disks['partitions'] ?? [] as $x)
                        @php $low = ($x['total_gb'] ?? 0) > 0 && ($x['free_gb'] / $x['total_gb']) < 0.1; @endphp
                        <small class="{{ $low ? 'low' : '' }}">{{ $x['drive'] }} sisa {{ $x['free_gb'] }} / {{ $x['total_gb'] }} GB</small><br>
                    @endforeach
                </td>
                <td>{{ $p->os }}</td>
                <td>{{ $p->uptime_h }} jam</td>
                <td>{{ $p->last_report_at?->format('d-m-Y H:i:s') }}</td>
                <td>
                    <form method="POST" action="{{ route('pcs.sync', $p) }}">
                        @csrf
                        <button class="btn sm" @disabled($p->sync_pending)>
                            {{ $p->sync_pending ? 'Menunggu…' : 'Sinkron' }}
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

<script>
function filterRows() {
    const q = document.getElementById('q').value.toLowerCase();
    document.querySelectorAll('#t tbody tr').forEach(r => {
        r.style.display = r.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
```

### 3.8 Jalankan

```
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

Dashboard: `http://IP-SERVER:8000/pcs`

Tes API tanpa agent (Command Prompt Windows):

```
curl -X POST http://IP-SERVER:8000/api/poll ^
  -H "X-API-Key: isi-token-acak-kamu" -H "Accept: application/json" -H "Content-Type: application/json" ^
  -d "{\"uid\":\"test-1\",\"hostname\":\"PC-TEST\"}"
```

Hasil `{"sync":true}` (PC belum terdaftar) berarti API dan token sudah benar.

---

## 4. Bagian Agent (PC client)

### 4.1 Kode lengkap: `agent.py`

Ubah dua baris konfigurasi di bagian atas (`BASE_URL` dan `API_KEY`).

```python
"""
Agent inventaris PC kantor (Windows).

Tes     : py agent.py --test    -> tampilkan data di terminal
          py agent.py --poll    -> tes koneksi poll ke Laravel
          py agent.py --once    -> kirim data sekali, tampilkan status
Jalan   : py agent.py           -> mode agent (poll tiap 30 detik, lapor tiap 15 menit
                                   atau langsung saat tombol Sinkron diklik di Laravel)
Build   : pyinstaller --onefile --noconsole agent.py
"""
import json
import platform
import socket
import subprocess
import sys
import time
import winreg

import psutil
import requests

# ================== KONFIGURASI ==================
BASE_URL = "http://192.168.1.10:8000/api"   # alamat Laravel + /api
API_KEY = "GANTI-DENGAN-TOKEN-RAHASIA"      # harus sama dengan AGENT_API_KEY di .env Laravel
REPORT_INTERVAL = 900                       # lapor rutin tiap 900 detik (15 menit)
POLL_INTERVAL = 30                          # cek perintah sinkron tiap 30 detik
# =================================================

REPORT_URL = f"{BASE_URL}/report"
POLL_URL = f"{BASE_URL}/poll"
HEADERS = {"X-API-Key": API_KEY}
NO_WINDOW = 0x08000000  # supaya PowerShell tidak memunculkan jendela


def ps(command):
    """Jalankan perintah PowerShell, kembalikan output teks ('' jika gagal)."""
    try:
        r = subprocess.run(
            ["powershell", "-NoProfile", "-NonInteractive", "-Command", command],
            capture_output=True, text=True, timeout=30, creationflags=NO_WINDOW,
        )
        return r.stdout.strip()
    except Exception:
        return ""


def cpu_name():
    try:
        key = winreg.OpenKey(
            winreg.HKEY_LOCAL_MACHINE,
            r"HARDWARE\DESCRIPTION\System\CentralProcessor\0",
        )
        name, _ = winreg.QueryValueEx(key, "ProcessorNameString")
        return " ".join(name.split())
    except Exception:
        return platform.processor()


def machine_uid():
    """ID unik PC (UUID motherboard) -> tidak berubah walau PC di-rename."""
    uid = ps("(Get-CimInstance Win32_ComputerSystemProduct).UUID")
    return uid or socket.gethostname()


def system_info():
    out = ps(
        "$s=Get-CimInstance Win32_ComputerSystem;"
        "$b=Get-CimInstance Win32_BIOS;"
        "[pscustomobject]@{manufacturer=$s.Manufacturer;model=$s.Model;"
        "serial=$b.SerialNumber;user=$s.UserName} | ConvertTo-Json"
    )
    try:
        return json.loads(out)
    except Exception:
        return {}


def physical_disks():
    """Model & tipe disk fisik (SSD/HDD)."""
    out = ps(
        "Get-PhysicalDisk | Select-Object FriendlyName,MediaType,Size | ConvertTo-Json"
    )
    try:
        data = json.loads(out)
        if isinstance(data, dict):
            data = [data]
        return [
            {
                "model": d.get("FriendlyName"),
                "type": d.get("MediaType"),
                "size_gb": round((d.get("Size") or 0) / 1024**3),
            }
            for d in data
        ]
    except Exception:
        return []


def local_ip():
    """IP yang dipakai untuk keluar jaringan (lebih akurat daripada gethostbyname)."""
    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    try:
        s.connect(("8.8.8.8", 80))  # tidak mengirim data, hanya memilih interface
        return s.getsockname()[0]
    except Exception:
        return socket.gethostbyname(socket.gethostname())
    finally:
        s.close()


def mac_address():
    ip = local_ip()
    for nic, addrs in psutil.net_if_addrs().items():
        if any(a.address == ip for a in addrs):
            for a in addrs:
                if a.family == psutil.AF_LINK:
                    return a.address.replace("-", ":").lower()
    return ""


def partitions():
    result = []
    for p in psutil.disk_partitions():
        if "cdrom" in p.opts or p.fstype == "":
            continue
        try:
            u = psutil.disk_usage(p.mountpoint)
            result.append({
                "drive": p.device,
                "total_gb": round(u.total / 1024**3, 1),
                "free_gb": round(u.free / 1024**3, 1),
            })
        except (PermissionError, OSError):
            pass
    return result


def collect(uid=None):
    info = system_info()
    return {
        "uid": uid or machine_uid(),
        "hostname": socket.gethostname(),
        "manufacturer": info.get("manufacturer"),
        "model": info.get("model"),
        "serial": info.get("serial"),
        "login_user": info.get("user"),
        "os": platform.platform(),
        "cpu": cpu_name(),
        "cpu_cores": psutil.cpu_count(logical=False),
        "cpu_threads": psutil.cpu_count(logical=True),
        "ram_gb": round(psutil.virtual_memory().total / 1024**3, 1),
        "physical_disks": physical_disks(),
        "partitions": partitions(),
        "ip": local_ip(),
        "mac": mac_address(),
        "uptime_h": round((time.time() - psutil.boot_time()) / 3600, 1),
    }


def send(uid):
    """Kirim data lengkap ke Laravel. Return status HTTP."""
    r = requests.post(REPORT_URL, json=collect(uid), headers=HEADERS, timeout=20)
    return r.status_code


def poll(uid):
    """Tanya Laravel: apakah ada permintaan sinkron? Sekaligus 'cek-in' (status online)."""
    r = requests.post(
        POLL_URL,
        json={"uid": uid, "hostname": socket.gethostname()},
        headers=HEADERS,
        timeout=10,
    )
    r.raise_for_status()
    return bool(r.json().get("sync"))


def run():
    uid = machine_uid()
    last_report = 0.0
    while True:
        try:
            force = poll(uid)
        except Exception:
            force = False  # server mati / jaringan putus -> coba lagi nanti

        if force or time.time() - last_report >= REPORT_INTERVAL:
            try:
                if send(uid) == 200:
                    last_report = time.time()
            except Exception:
                pass  # gagal kirim -> diulang pada putaran poll berikutnya

        time.sleep(POLL_INTERVAL)


if __name__ == "__main__":
    if "--test" in sys.argv:
        print(json.dumps(collect(), indent=2, ensure_ascii=False))
    elif "--poll" in sys.argv:
        print("sync diminta:", poll(machine_uid()))
    elif "--once" in sys.argv:
        print("Status kirim:", send(machine_uid()))
    else:
        run()
```

### 4.2 Tes agent

```
pip install psutil requests
py agent.py --test     # tampilkan data PC di terminal (belum kirim ke mana pun)
py agent.py --poll     # tes cek-in ke Laravel, harus tercetak "sync diminta: True/False"
py agent.py --once     # kirim data sekali, "Status kirim: 200" berarti sukses
py agent.py            # mode agent penuh (loop)
```

Contoh output `--test`:

```json
{
  "uid": "4C4C4544-0042-3510-8052-B8C04F4B4D32",
  "hostname": "PC-ADMIN-01",
  "manufacturer": "Dell Inc.",
  "model": "OptiPlex 3070",
  "serial": "ABC1234",
  "login_user": "KANTOR\\budi",
  "os": "Windows-10-10.0.19045-SP0",
  "cpu": "Intel(R) Core(TM) i5-9500 CPU @ 3.00GHz",
  "cpu_cores": 6,
  "cpu_threads": 6,
  "ram_gb": 15.8,
  "physical_disks": [{"model": "SAMSUNG MZVLB256", "type": "SSD", "size_gb": 238}],
  "partitions": [{"drive": "C:\\", "total_gb": 237.8, "free_gb": 120.4}],
  "ip": "192.168.1.25",
  "mac": "aa:bb:cc:dd:ee:ff",
  "uptime_h": 5.3
}
```

### 4.3 Jadikan .exe

```
pip install pyinstaller
pyinstaller --onefile --noconsole agent.py
```

Hasil: `dist\agent.exe`. Satu file ini cukup, PC client tidak perlu menginstal Python.

---

## 5. Deploy ke PC client

### 5.1 Manual (per PC), jalankan Command Prompt sebagai Administrator

```
mkdir C:\ProgramData\InventarisAgent
copy agent.exe C:\ProgramData\InventarisAgent\agent.exe

schtasks /Create /TN "InventarisAgent" /TR "C:\ProgramData\InventarisAgent\agent.exe" /SC ONSTART /RU SYSTEM /RL HIGHEST /F
schtasks /Run /TN "InventarisAgent"
```

Agent akan jalan otomatis setiap PC menyala, tanpa jendela dan tanpa perlu login.

Cek apakah berjalan:

```
tasklist | findstr agent.exe
```

Hapus/uninstall:

```
schtasks /End /TN "InventarisAgent"
schtasks /Delete /TN "InventarisAgent" /F
taskkill /F /IM agent.exe
del C:\ProgramData\InventarisAgent\agent.exe
```

### 5.2 Massal lewat GPO (domain)

1. Taruh `agent.exe` di shared folder yang bisa dibaca semua PC (mis. `\\SERVER\deploy\agent.exe`).
2. Group Policy → *Computer Configuration → Preferences → Control Panel Settings → Scheduled Tasks → New → Scheduled Task (at least Windows 7)*.
3. Isi: Action *Create*, user **SYSTEM**, *Run with highest privileges*, Trigger **At startup**, Action *Start a program* → `\\SERVER\deploy\agent.exe`.

### 5.3 Update agent

Kalau ada perubahan kode: build ulang, hentikan task (`schtasks /End`), ganti `agent.exe`, lalu jalankan lagi (`schtasks /Run`).

---

## 6. Cara memakai tombol sinkron

1. Buka `http://IP-SERVER:8000/pcs`.
2. **Sinkronkan semua**: semua PC diminta mengirim data terbaru.
3. **Sinkron** di baris PC: hanya PC itu yang diminta.
4. PC yang diminta menampilkan badge **Menunggu sinkron…**. Halaman refresh sendiri tiap 10 detik, dan badge hilang setelah data masuk (biasanya ±30 detik).
5. PC offline tetap *menunggu* dan otomatis sinkron begitu menyala.

Mengubah waktu:

| Pengaturan | Lokasi | Default |
|---|---|---|
| Seberapa cepat agent merespons tombol | `POLL_INTERVAL` di `agent.py` | 30 detik |
| Lapor rutin tanpa klik | `REPORT_INTERVAL` di `agent.py` | 15 menit |
| Batas dianggap offline | `subMinutes(3)` di `Pc.php` | 3 menit |

> Kalau `POLL_INTERVAL` diperkecil, sesuaikan juga batas offline. Jangan kurang dari 3× nilai poll.

---

## 7. Troubleshooting

| Gejala | Penyebab / solusi |
|---|---|
| `--poll` / `--once` timeout | Server tidak terjangkau. Cek IP, port, dan firewall server (buka TCP 8000, atau 80/443 kalau pakai Nginx/Apache). |
| HTTP **401** | Token beda. Samakan `API_KEY` di agent dengan `AGENT_API_KEY` di `.env`, lalu `php artisan config:clear`. |
| HTTP **404** di `/api/...` | Route API belum aktif. Jalankan `php artisan install:api`, lalu `php artisan route:list` dan pastikan ada `api/report` dan `api/poll`. |
| HTTP **422** | Ada data gagal validasi. Kirim dengan header `Accept: application/json` untuk melihat detail error. |
| HTTP **419** | Itu CSRF, artinya request masuk ke route web, bukan API. Pastikan URL agent berakhiran `/api`. |
| HTTP **500** | Lihat `storage/logs/laravel.log`. Biasanya migration belum dijalankan. |
| Tombol sinkron diklik tapi badge tidak hilang | Agent tidak jalan di PC itu (`tasklist \| findstr agent.exe`), PC offline, atau agent tidak bisa menjangkau server. |
| Semua PC tampil Offline | Cek apakah agent berjalan, dan pastikan jam server tidak jauh berbeda dari jam sebenarnya. |
| Dua PC muncul sebagai satu | UUID motherboard kembar (biasa pada PC hasil clone/PC rakitan murah). Ganti sumber `uid` di `machine_uid()` dengan serial BIOS atau MAC. |
| Satu PC muncul ganda | Agent berjalan dua kali (task dibuat dua kali). Hapus task ganda dan pastikan hanya ada satu `agent.exe` di Task Manager. |
| Tipe disk `Unspecified` | Normal untuk beberapa disk/RAID/virtual machine. |
| Nama user kosong | Tidak ada user yang sedang login di PC itu. |

---

## 8. Keamanan

- Ganti `AGENT_API_KEY` dengan token acak panjang. Jangan pakai nilai contoh.
- Token tertanam di `agent.exe`. Siapa pun yang punya file itu bisa membacanya, jadi anggap token ini hanya untuk jaringan kantor, bukan untuk internet.
- Batasi akses server ke jaringan kantor saja (firewall). Untuk jaringan yang lebih luas, pakai HTTPS (Nginx/Apache + sertifikat).
- Dashboard `/pcs` saat ini terbuka untuk siapa saja yang bisa mengakses server, dan tombol sinkron juga. Tambahkan login (mis. Laravel Breeze) dan bungkus route web dengan `->middleware('auth')`.
- Untuk produksi, jangan pakai `php artisan serve`. Gunakan Nginx/Apache + PHP-FPM, atau Laragon/XAMPP di Windows.
- Agent hanya mengambil data hardware dan sistem (bukan aktivitas pribadi karyawan). Sebaiknya informasikan ke karyawan dan tuangkan dalam kebijakan IT kantor.

---

## 9. Ide pengembangan

- Halaman detail per PC dan riwayat perubahan spesifikasi (mis. RAM diganti).
- Daftar software terinstall.
- Notifikasi disk hampir penuh atau PC offline terlalu lama.
- Export ke Excel/PDF.
- Login dan role (admin dan viewer).
