"""
Agent inventaris PC kantor (Windows).

Alur:
1. Saat PC dinyalakan / agent start: langsung kirim spesifikasi lengkap ke Laravel (1x).
2. Setelah itu: murni siaga (standby). Hanya cek-in ringan tiap 30 detik (menjaga status online)
   dan hanya akan mengirim data spek JIKA tombol Sinkron diklik di web Laravel.

Penggunaan:
  py agent.py --test    -> cek pembacaan data PC di terminal
  py agent.py --poll    -> tes cek-in / heartbeat ke Laravel
  py agent.py --once    -> kirim data spek sekali, tampilkan respons
  py agent.py           -> jalankan agent background (startup & standby)

Build .exe:
  pyinstaller --onefile --noconsole agent.py
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
# Ganti dengan IP/domain komputer server Laravel di jaringan kantor (misal: "http://192.168.1.10:8000/api")
BASE_URL = "http://127.0.0.1:8000/api"
API_KEY = "52dd6df51a1a9c66d15dd26f812b6a67412ac2079ae4bd79"
POLL_INTERVAL = 30  # Detik cek-in heartbeat & deteksi tombol sinkron
# =================================================

REPORT_URL = f"{BASE_URL}/report"
POLL_URL = f"{BASE_URL}/poll"
HEADERS = {
    "X-API-Key": API_KEY,
    "Accept": "application/json",
    "Content-Type": "application/json",
}
NO_WINDOW = 0x08000000  # Supaya PowerShell tidak memunculkan popup jendela


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


def local_ip():
    """IP lokal yang dipakai keluar jaringan."""
    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    try:
        s.connect(("8.8.8.8", 80))
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


def machine_uid():
    """ID unik PC (UUID motherboard) -> tidak berubah walau PC di-rename."""
    uid = ps("(Get-CimInstance Win32_ComputerSystemProduct).UUID")
    if uid and uid not in ["00000000-0000-0000-0000-000000000000", "FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF"]:
        return uid
    # Fallback jika UUID motherboard default / hasil kloning
    mac = mac_address()
    if mac:
        return f"mac-{mac}"
    return socket.gethostname()


def system_info():
    out = ps(
        "$s=Get-CimInstance Win32_ComputerSystem;"
        "$b=Get-CimInstance Win32_BIOS;"
        "$mb=Get-CimInstance Win32_BaseBoard;"
        "$os=Get-CimInstance Win32_OperatingSystem;"
        "[pscustomobject]@{"
        "manufacturer=$s.Manufacturer;"
        "model=$s.Model;"
        "serial=$b.SerialNumber;"
        "user=$s.UserName;"
        "board_mfg=$mb.Manufacturer;"
        "board_prod=$mb.Product;"
        "bios_version=$b.SMBIOSBIOSVersion;"
        "os_caption=$os.Caption;"
        "os_build=$os.BuildNumber;"
        "os_arch=$os.OSArchitecture"
        "} | ConvertTo-Json"
    )
    try:
        data = json.loads(out)
        board_mfg = (data.get("board_mfg") or "").strip()
        board_prod = (data.get("board_prod") or "").strip()
        invalid_prod = ["default string", "base board product name", "to be filled by o.e.m.", "none", ""]

        if board_prod and board_prod.lower() not in invalid_prod:
            if board_mfg.lower() in board_prod.lower():
                mb_name = board_prod
            else:
                mb_name = f"{board_mfg} {board_prod}".strip()
        else:
            mfg = data.get("manufacturer") or board_mfg
            mdl = data.get("model") or "Motherboard"
            mb_name = f"{mfg} {mdl}".strip()

        data["motherboard"] = " ".join(mb_name.split()) if mb_name else None
        data["bios_version"] = " ".join((data.get("bios_version") or "").split()) or None

        # Deteksi OS akurat (Windows 11 vs Windows 10)
        os_cap = (data.get("os_caption") or "").replace("Microsoft ", "").strip()
        os_build = str(data.get("os_build") or "")
        os_arch = (data.get("os_arch") or "").strip()

        try:
            if os_build and int(os_build) >= 22000:
                os_cap = os_cap.replace("Windows 10", "Windows 11")
        except ValueError:
            pass

        if not os_cap:
            os_cap = platform.platform()
        elif os_arch and os_arch.lower() not in os_cap.lower():
            os_cap = f"{os_cap} ({os_arch})"

        data["os"] = os_cap
        return data
    except Exception:
        return {}


def ram_info():
    """Ambil informasi mendalam tentang RAM (DDR, MHz, slot, merk)."""
    out = ps(
        "Get-CimInstance Win32_PhysicalMemory | "
        "Select-Object Manufacturer, Capacity, Speed, ConfiguredClockSpeed, "
        "SMBIOSMemoryType, DeviceLocator, PartNumber | ConvertTo-Json"
    )
    type_map = {
        20: "DDR", 21: "DDR2", 22: "DDR2", 24: "DDR3",
        26: "DDR4", 30: "LPDDR4", 34: "DDR5", 35: "LPDDR5",
    }
    details = []
    max_speed = 0
    main_type = None

    try:
        data = json.loads(out)
        if isinstance(data, dict):
            data = [data]
        for d in data:
            cap = d.get("Capacity") or 0
            size_gb = round(cap / 1024**3, 1)
            spd = d.get("Speed") or 0
            clk = d.get("ConfiguredClockSpeed") or spd
            raw_type = d.get("SMBIOSMemoryType") or 0
            t = type_map.get(raw_type)

            if not t:
                if spd >= 4800:
                    t = "DDR5"
                elif spd >= 2133:
                    t = "DDR4"
                elif spd >= 1066:
                    t = "DDR3"
                else:
                    t = "DDR"

            if spd > max_speed:
                max_speed = spd
            if not main_type:
                main_type = t

            details.append({
                "slot": (d.get("DeviceLocator") or "DIMM").strip(),
                "size_gb": size_gb,
                "speed": spd,
                "clock": clk,
                "type": t,
                "manufacturer": (d.get("Manufacturer") or "-").strip(),
                "part_number": (d.get("PartNumber") or "-").strip(),
            })
    except Exception:
        pass

    return {
        "type": main_type or "DDR4",
        "speed": max_speed if max_speed > 0 else None,
        "details": details,
    }


def physical_disks():
    """Model, tipe (SSD/HDD), antarmuka (NVMe/SATA), dan status kesehatan disk."""
    out = ps(
        "Get-PhysicalDisk | Select-Object FriendlyName, MediaType, BusType, Size, HealthStatus | ConvertTo-Json"
    )
    try:
        data = json.loads(out)
        if isinstance(data, dict):
            data = [data]
        return [
            {
                "model": d.get("FriendlyName") or "-",
                "type": d.get("MediaType") or "Disk",
                "bus_type": d.get("BusType") or "SATA",
                "size_gb": round((d.get("Size") or 0) / 1024**3),
                "health": d.get("HealthStatus") or "Healthy",
            }
            for d in data
        ]
    except Exception:
        return []


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


def gpu_info():
    """Ambil informasi Kartu Grafis / VGA (GPU)."""
    out = ps(
        "Get-CimInstance Win32_VideoController | "
        "Select-Object Name, VideoProcessor, AdapterRAM, DriverVersion, "
        "CurrentHorizontalResolution, CurrentVerticalResolution, CurrentRefreshRate | "
        "ConvertTo-Json"
    )
    if not out:
        return {"primary": None, "details": []}
    try:
        raw = json.loads(out)
        items = [raw] if isinstance(raw, dict) else (raw if isinstance(raw, list) else [])
        gpus = []
        for g in items:
            name = (g.get("Name") or g.get("VideoProcessor") or "").strip()
            if not name:
                continue
            ram_b = g.get("AdapterRAM") or 0
            vram_gb = round(ram_b / (1024**3), 1) if ram_b and ram_b > 0 else None
            w = g.get("CurrentHorizontalResolution")
            h = g.get("CurrentVerticalResolution")
            hz = g.get("CurrentRefreshRate")
            res = f"{w}x{h} @ {hz}Hz" if (w and h and hz) else (f"{w}x{h}" if (w and h) else None)

            gpus.append({
                "name": name,
                "vram_gb": vram_gb,
                "resolution": res,
                "driver_version": g.get("DriverVersion"),
            })

        primary = None
        if gpus:
            discrete = [x for x in gpus if any(k in x["name"].lower() for k in ["nvidia", "geforce", "radeon", "rtx", "gtx"])]
            primary = discrete[0]["name"] if discrete else gpus[0]["name"]

        return {"primary": primary, "details": gpus}
    except Exception:
        return {"primary": None, "details": []}


def monitors_info():
    """Ambil informasi Monitor / Layar yang terpasang."""
    out = ps(
        "$m = Get-CimInstance -Namespace root\\wmi -ClassName WmiMonitorID -ErrorAction SilentlyContinue; "
        "if ($m) { "
        "  $m | ForEach-Object { "
        "    $mf = [char[]]($_.ManufacturerName | Where-Object {$_ -ne 0}) -join ''; "
        "    $md = [char[]]($_.UserFriendlyName | Where-Object {$_ -ne 0}) -join ''; "
        "    $sn = [char[]]($_.SerialNumberID | Where-Object {$_ -ne 0}) -join ''; "
        "    [pscustomobject]@{ Manufacturer=$mf; Model=$md; Serial=$sn } "
        "  } | ConvertTo-Json "
        "} else { "
        "  Get-CimInstance Win32_PnPEntity | Where-Object { $_.PNPClass -eq 'Monitor' } | "
        "  Select-Object Name, DeviceID | ConvertTo-Json "
        "}"
    )
    if not out:
        return []
    try:
        raw = json.loads(out)
        items = [raw] if isinstance(raw, dict) else (raw if isinstance(raw, list) else [])
        mfg_map = {
            "ACR": "Acer", "GSM": "LG", "SAM": "Samsung", "DEL": "Dell",
            "HWP": "HP", "HEW": "HP", "ACI": "ASUS", "AUS": "ASUS",
            "AOC": "AOC", "BNQ": "BenQ", "LEN": "Lenovo", "PHL": "Philips",
            "VSC": "ViewSonic", "MSI": "MSI", "SNY": "Sony"
        }
        monitors = []
        for m in items:
            raw_mfg = (m.get("Manufacturer") or "").strip()
            model = (m.get("Model") or m.get("Name") or "").strip()
            serial = (m.get("Serial") or "").strip()
            mfg_name = mfg_map.get(raw_mfg.upper(), raw_mfg)

            if model or mfg_name:
                monitors.append({
                    "manufacturer": mfg_name or raw_mfg,
                    "model": model or "Monitor",
                    "serial": serial if serial else None,
                })
        return monitors
    except Exception:
        return []


def battery_info():
    """Ambil informasi Baterai (khusus Laptop)."""
    out = ps(
        "Get-CimInstance Win32_Battery -ErrorAction SilentlyContinue | "
        "Select-Object EstimatedChargeRemaining, BatteryStatus, DesignCapacity, FullChargeCapacity | "
        "ConvertTo-Json"
    )
    if not out:
        return {"is_laptop": False, "status": "Desktop (Tanpa Baterai)"}
    try:
        raw = json.loads(out)
        b = raw[0] if isinstance(raw, list) else raw
        status_map = {
            1: "Discharging", 2: "AC Connected (Charging)", 3: "Fully Charged",
            4: "Low", 5: "Critical", 6: "Charging", 7: "Charging and High",
            8: "Charging and Low", 9: "Charging and Critical", 10: "Undefined", 11: "Partially Charged"
        }
        st_code = b.get("BatteryStatus") or 0
        status_text = status_map.get(st_code, "Baterai Aktif")

        pct = b.get("EstimatedChargeRemaining")
        design = b.get("DesignCapacity") or 0
        full = b.get("FullChargeCapacity") or 0
        health = round((full / design) * 100, 1) if (design > 0 and full > 0) else None

        return {
            "is_laptop": True,
            "percent": pct,
            "status": status_text,
            "health_percent": health,
        }
    except Exception:
        return {"is_laptop": False, "status": "Desktop (Tanpa Baterai)"}


def get_ultraviewer_id():
    """Deteksi Partner ID UltraViewer dari Registry Windows jika terpasang."""
    paths = [
        (winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE\WOW6432Node\UltraViewer"),
        (winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE\UltraViewer"),
        (winreg.HKEY_CURRENT_USER, r"Software\UltraViewer"),
    ]
    for root, subkey in paths:
        try:
            with winreg.OpenKey(root, subkey, 0, winreg.KEY_READ) as k:
                for name in ("PreferID", "ID"):
                    try:
                        val, _ = winreg.QueryValueEx(k, name)
                        if val:
                            return str(val).strip()
                    except FileNotFoundError:
                        pass
        except OSError:
            pass
    return None


def collect(uid=None):
    info = system_info()
    ram = ram_info()
    gpus = gpu_info()
    return {
        "uid": uid or machine_uid(),
        "hostname": socket.gethostname(),
        "alias": None,
        "stock_status": "digunakan",
        "department": None,
        "location": None,
        "ultraviewer_id": get_ultraviewer_id(),
        "manufacturer": info.get("manufacturer"),
        "model": info.get("model"),
        "motherboard": info.get("motherboard"),
        "bios_version": info.get("bios_version"),
        "serial": info.get("serial"),
        "login_user": info.get("user"),
        "os": info.get("os") or platform.platform(),
        "cpu": cpu_name(),
        "cpu_cores": psutil.cpu_count(logical=False),
        "cpu_threads": psutil.cpu_count(logical=True),
        "gpu": gpus.get("primary"),
        "gpu_details": gpus.get("details"),
        "monitors": monitors_info(),
        "battery": battery_info(),
        "ram_gb": round(psutil.virtual_memory().total / 1024**3, 1),
        "ram_type": ram.get("type"),
        "ram_speed": ram.get("speed"),
        "ram_details": ram.get("details"),
        "physical_disks": physical_disks(),
        "partitions": partitions(),
        "ip": local_ip(),
        "mac": mac_address(),
        "uptime_h": round((time.time() - psutil.boot_time()) / 3600, 1),
    }


def send(uid):
    """Kirim data lengkap ke Laravel. Return status HTTP."""
    data = collect(uid)
    r = requests.post(REPORT_URL, json=data, headers=HEADERS, timeout=20)
    return r.status_code


def poll(uid):
    """Tanya Laravel: apakah ada permintaan sinkron? Sekaligus 'cek-in' heartbeat status online."""
    r = requests.post(
        POLL_URL,
        json={
            "uid": uid,
            "hostname": socket.gethostname(),
            "ultraviewer_id": get_ultraviewer_id(),
        },
        headers=HEADERS,
        timeout=10,
    )
    r.raise_for_status()
    return bool(r.json().get("sync"))


def run():
    uid = machine_uid()
    hostname = socket.gethostname()
    print(f"[*] Agent Inventaris PC Aktif: {hostname} (UID: {uid})")

    # 1. Saat PC dinyalakan / startup: langsung kirim spesifikasi lengkap 1x
    print("[*] Startup: Mengirim data spesifikasi awal ke server...")
    try:
        status = send(uid)
        if status == 200:
            print("[+] Spesifikasi awal berhasil terkirim ke Laravel!")
        else:
            print(f"[!] Gagal mengirim spesifikasi awal (HTTP {status}).")
    except Exception as e:
        print(f"[!] Server belum terhubung saat startup ({e}). Akan otomatis dilaporkan saat poll.")

    # 2. Masuk mode siaga (standby)
    print(f"[*] Mode siaga: Heartbeat setiap {POLL_INTERVAL} detik. Menunggu perintah sinkronisasi...")
    while True:
        try:
            # Poll menjaga status PC tetap 'Online' dan mengecek tombol Sinkron di web
            need_sync = poll(uid)
            if need_sync:
                print("[*] Permintaan sinkronisasi diterima dari web! Memperbarui data...")
                status = send(uid)
                if status == 200:
                    print("[+] Data spesifikasi terbaru berhasil dikirim!")
                else:
                    print(f"[!] Gagal update data (HTTP {status}).")
        except Exception:
            pass  # Jaringan offline atau server down sementara -> coba lagi di putaran berikutnya

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
