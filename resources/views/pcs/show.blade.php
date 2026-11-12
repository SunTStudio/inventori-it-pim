<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Refresh otomatis jika sedang menunggu sinkronisasi --}}
    <meta http-equiv="refresh" content="{{ $pc->sync_pending ? 10 : 60 }}">
    <title>{{ $pc->display_name }} - Detail Spesifikasi PC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script>
        if (typeof Swal === 'undefined') {
            document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"><\/script>');
        }
    </script>
    <style>
        :root {
            --bg-main: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #eff6ff;
            --success: #16a34a;
            --success-light: #dcfce7;
            --danger: #dc2626;
            --danger-light: #fee2e2;
            --warning-bg: #fef3c7;
            --warning-text: #92400e;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
            line-height: 1.5;
        }

        header {
            background: #0f172a;
            color: #ffffff;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: color 0.15s;
        }

        .btn-back:hover {
            color: #ffffff;
        }

        .wrap {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px 32px 56px;
        }

        .flash {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .hero-banner {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
        }

        .hero-title-area {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .hero-icon {
            font-size: 42px;
            line-height: 1;
            background: #eff6ff;
            padding: 14px;
            border-radius: 14px;
            border: 1px solid #dbeafe;
        }

        .hero-title {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .hero-subtitle {
            margin-top: 4px;
            color: var(--text-muted);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 700;
        }

        .status-badge.online {
            background: var(--success-light);
            color: #15803d;
        }

        .status-badge.offline {
            background: var(--danger-light);
            color: #b91c1c;
        }

        .sync-badge {
            background: var(--warning-bg);
            color: var(--warning-text);
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 9px;
            padding: 9px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.15s;
        }

        .btn:hover {
            background: var(--primary-hover);
        }

        .btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        .btn-outline {
            background: #ffffff;
            border: 1px solid var(--border-color);
            color: #334155;
        }

        .btn-outline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .btn-danger {
            background: #ffffff;
            border: 1px solid #fecaca;
            color: var(--danger);
        }

        .btn-danger:hover {
            background: #fef2f2;
            border-color: #f87171;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .metric-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .metric-icon {
            font-size: 26px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #f1f5f9;
        }

        .metric-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .metric-val {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
        }

        .layout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        @media (max-width: 992px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            margin-bottom: 24px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .card-header h2 {
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #0f172a;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f8fafc;
            font-size: 13px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-muted);
            font-weight: 500;
            min-width: 140px;
        }

        .info-value {
            font-weight: 600;
            color: #0f172a;
            text-align: right;
            word-break: break-all;
        }

        .disk-box {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 12px;
        }

        .disk-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .disk-progress-wrap {
            background: #e2e8f0;
            height: 10px;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .disk-progress-bar {
            height: 100%;
            background: #2563eb;
            border-radius: 5px;
            transition: width 0.3s;
        }

        .disk-progress-bar.low {
            background: #ef4444;
        }

        .disk-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
        }

        .copy-pill {
            cursor: pointer;
            padding: 2px 7px;
            background: #f1f5f9;
            border-radius: 4px;
            font-size: 12px;
            font-family: monospace;
            border: 1px solid #e2e8f0;
            transition: all 0.15s;
        }

        .copy-pill:hover {
            background: #e2e8f0;
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #334155;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 9px 12px;
            font-size: 13px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            outline: none;
            font-family: inherit;
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
    </style>
</head>
<body>

<header>
    <div>
        <a href="{{ route('pcs.index') }}" class="btn-back">
            <span>←</span> Kembali ke Dashboard Inventaris
        </a>
    </div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 13px; color: #94a3b8; margin-right: 4px;">
            👤 <strong style="color: #ffffff;">{{ auth()->user()->name ?? 'Admin IT' }}</strong>
        </span>
        <form method="POST" action="{{ route('pcs.sync', $pc) }}" style="display: inline;" onsubmit="return confirmSyncSingle(event, this, '{{ addslashes($pc->display_name) }}')">
            @csrf
            <button type="submit" class="btn" @disabled($pc->sync_pending)>
                {{ $pc->sync_pending ? '⏳ Menunggu Sinkron...' : '🔄 Sinkronkan Data PC' }}
            </button>
        </form>
        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-outline" style="color: #f87171; border-color: #571b1b; background: #261111;" title="Keluar dari akun admin">
                🚪 Keluar
            </button>
        </form>
    </div>
</header>

<div class="wrap">
    @if (session('status'))
        <div class="flash">
            <span>✅</span> {{ session('status') }}
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: '{{ addslashes(session('status')) }}',
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true
                });
            });
        </script>
    @endif

    {{-- Hero Banner --}}
    <div class="hero-banner">
        <div class="hero-title-area">
            <div class="hero-icon">💻</div>
            <div>
                <div class="hero-title">
                    <span>{{ $pc->display_name }}</span>
                    @if ($pc->is_online)
                        <span class="status-badge online">● Online</span>
                    @else
                        <span class="status-badge offline">○ Offline</span>
                    @endif
                    @if ($pc->stock_status === 'bebas')
                        <span class="status-badge" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">📦 Stock Bebas</span>
                    @else
                        <span class="status-badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">🏢 Stock Digunakan</span>
                    @endif
                    @if ($pc->sync_pending)
                        <span class="sync-badge">Menunggu Sinkronisasi</span>
                    @endif
                </div>
                <div class="hero-subtitle">
                    <span>Hostname: <code style="font-weight: 600; color: #2563eb;">{{ $pc->hostname }}</code></span>
                    <span>•</span>
                    <span>IP: <code class="copy-pill" onclick="copyText('{{ $pc->ip }}')" title="Klik untuk salin">{{ $pc->ip ?? '-' }} 📋</code></span>
                    <span>•</span>
                    <span>User Login: <strong>{{ $pc->login_user ?: 'Tidak ada' }}</strong></span>
                </div>
            </div>
        </div>

        <div class="hero-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
            {{-- Remote PC Button --}}
            <button type="button" class="btn btn-outline" style="color: #6d28d9; border-color: #ddd6fe; background: #faf5ff; font-weight: 700;" onclick="openRemoteModal('{{ addslashes($pc->display_name) }}', '{{ $pc->ip }}', '{{ route('pcs.remote-rdp', $pc) }}', '{{ route('pcs.remote-radmin', $pc) }}')">
                ⚡ Remote Desktop
            </button>

            {{-- Cetak Stiker Label QR --}}
            <a href="{{ route('pcs.print-label', $pc) }}" target="_blank" class="btn btn-outline" style="color: #0f172a;" title="Buka format stiker siap cetak">
                🖨️ Cetak Stiker QR
            </a>

            <button type="button" class="btn btn-outline" onclick="document.getElementById('editSection').scrollIntoView({ behavior: 'smooth' })">
                ✏️ Edit Data
            </button>
        </div>
    </div>

    {{-- Early Warning Alerts Banner --}}
    @if ($pc->has_warnings)
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 14px; padding: 18px 22px; margin-bottom: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 15px; color: #b45309; margin-bottom: 8px;">
                <span>⚠️</span>
                <span>Peringatan Kondisi Perangkat Ditemukan</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 6px;">
                @foreach ($pc->warnings as $w)
                    <div style="font-size: 13px; font-weight: 600; color: {{ $w['severity'] === 'danger' ? '#b91c1c' : '#b45309' }}; display: flex; align-items: center; gap: 8px;">
                        <span>•</span>
                        <span>{{ $w['message'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Top Metrics --}}
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">⚡</div>
            <div>
                <div class="metric-label">Processor (CPU)</div>
                <div class="metric-val" style="font-size: 14px;">{{ Str::limit($pc->cpu, 40) }}</div>
                <div class="muted">{{ $pc->cpu_cores ?? '?' }} Cores / {{ $pc->cpu_threads ?? '?' }} Threads</div>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon">🧠</div>
            <div>
                <div class="metric-label">Total Memori RAM</div>
                <div class="metric-val">{{ $pc->ram_gb ?? '-' }} GB {{ $pc->ram_type ?? '' }}</div>
                <div class="muted">
                    {{ $pc->ram_speed ? $pc->ram_speed . ' MHz • ' : '' }}
                    {{ count($pc->ram_details ?? []) > 0 ? count($pc->ram_details) . ' Keping Terpasang' : 'DDR System Memory' }}
                </div>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon">💽</div>
            <div>
                <div class="metric-label">Disk Fisik</div>
                <div class="metric-val">{{ count($pc->disks['physical'] ?? []) }} Unit Storage</div>
                <div class="muted">{{ count($pc->disks['partitions'] ?? []) }} Partisi Drive Aktif</div>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon">⏱️</div>
            <div>
                <div class="metric-label">Sistem Uptime</div>
                <div class="metric-val">{{ $pc->uptime_h ?? '0' }} Jam</div>
                <div class="muted">Sejak boot terakhir</div>
            </div>
        </div>
    </div>

    {{-- Main Layout Grid --}}
    <div class="layout-grid">
        {{-- Left Column (Spesifikasi Teknis) --}}
        <div>
            {{-- Hardware & Identity Card --}}
            <div class="card">
                <div class="card-header">
                    <h2>⚙️ Identitas & Spesifikasi Perangkat Keras</h2>
                </div>
                <div class="info-row">
                    <span class="info-label">Manufaktur / Merk</span>
                    <span class="info-value">{{ $pc->manufacturer ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Model Komputer</span>
                    <span class="info-value">{{ $pc->model ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Motherboard</span>
                    <span class="info-value"><strong>{{ $pc->motherboard ?? '-' }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Versi BIOS</span>
                    <span class="info-value"><code>{{ $pc->bios_version ?? '-' }}</code></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Serial Number BIOS</span>
                    <span class="info-value"><code>{{ $pc->serial ?: '-' }}</code></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Motherboard UID</span>
                    <span class="info-value">
                        <code class="copy-pill" onclick="copyText('{{ $pc->uid }}')" title="Klik untuk salin">{{ $pc->uid }} 📋</code>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sistem Operasi</span>
                    <span class="info-value">{{ $pc->os ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">User Login Terakhir</span>
                    <span class="info-value">{{ $pc->login_user ?: '-' }}</span>
                </div>
            </div>

            {{-- Hardware Change Audit Trail Card --}}
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>📜 Riwayat Perubahan Hardware (Audit Trail)</h2>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 9999px;">
                        {{ $pc->hardwareLogs->count() }} Peristiwa
                    </span>
                </div>
                @forelse ($pc->hardwareLogs as $log)
                    @php
                        $badgeBg = '#eff6ff';
                        $badgeColor = '#1d4ed8';
                        if ($log->change_type === 'downgraded' || $log->change_type === 'removed') {
                            $badgeBg = '#fee2e2';
                            $badgeColor = '#b91c1c';
                        } elseif ($log->change_type === 'upgraded' || $log->change_type === 'added') {
                            $badgeBg = '#dcfce7';
                            $badgeColor = '#15803d';
                        }
                    @endphp
                    <div class="disk-box" style="margin-bottom: 8px;">
                        <div class="disk-header" style="margin-bottom: 4px;">
                            <span>
                                <strong>{{ $log->component }}</strong>: {{ $log->description }}
                            </span>
                            <span class="stock-badge" style="background: {{ $badgeBg }}; color: {{ $badgeColor }}; text-transform: uppercase;">
                                {{ $log->change_type }}
                            </span>
                        </div>
                        <div class="disk-meta" style="color: #64748b; font-size: 11px;">
                            <span>🕒 Terdeteksi: {{ $log->created_at->format('d F Y, H:i') }} ({{ $log->created_at->diffForHumans() }})</span>
                        </div>
                    </div>
                @empty
                    <div style="padding: 8px 0; color: #64748b; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                        <span>🛡️</span>
                        <span>Tidak ada perubahan hardware yang terdeteksi. Spesifikasi konsisten sejak PC didaftarkan.</span>
                    </div>
                @endforelse
            </div>

            {{-- GPU & Display Card --}}
            <div class="card">
                <div class="card-header">
                    <h2>🎮 Kartu Grafis (GPU) & Resolusi Layar</h2>
                </div>
                <div class="info-row">
                    <span class="info-label">GPU Utama</span>
                    <span class="info-value"><strong>{{ $pc->gpu ?: 'Standar Display Adapter' }}</strong></span>
                </div>
                @if (!empty($pc->gpu_details))
                    @foreach ($pc->gpu_details as $g)
                        <div class="disk-box" style="margin-top: 8px;">
                            <div class="disk-header" style="margin-bottom: 4px;">
                                <span>🎮 <strong>{{ $g['name'] ?? 'GPU' }}</strong></span>
                                @if (!empty($g['resolution']))
                                    <span class="pc-meta-badge" style="background: #eff6ff; color: #1d4ed8; font-weight: 700;">
                                        📺 {{ $g['resolution'] }}
                                    </span>
                                @endif
                            </div>
                            <div class="disk-meta">
                                <span>VRAM Fisik: <strong>{{ !empty($g['vram_gb']) ? $g['vram_gb'].' GB' : 'Shared System Memory' }}</strong></span>
                                <span>Driver: <code>{{ $g['driver_version'] ?? '-' }}</code></span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Monitors Card --}}
            <div class="card">
                <div class="card-header">
                    <h2>🖥️ Monitor & Layar Terhubung</h2>
                </div>
                @if (!empty($pc->monitors) && count($pc->monitors) > 0)
                    @foreach ($pc->monitors as $idx => $mon)
                        <div class="disk-box" style="margin-bottom: 8px;">
                            <div class="disk-header" style="margin-bottom: 4px;">
                                <span>🖥️ <strong>Monitor #{{ $idx + 1 }}: {{ $mon['model'] ?? 'Display' }}</strong></span>
                                <span class="pc-meta-badge" style="background: #f1f5f9; color: #334155;">
                                    {{ $mon['manufacturer'] ?? 'OEM' }}
                                </span>
                            </div>
                            <div class="disk-meta">
                                <span>Merk / Pabrikan: <strong>{{ $mon['manufacturer'] ?? '-' }}</strong></span>
                                <span>Serial Number: <code>{{ $mon['serial'] ?? '-' }}</code></span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="info-row">
                        <span class="info-label">Status Monitor</span>
                        <span class="info-value muted">Monitor Generic / Standar PnP Display</span>
                    </div>
                @endif
            </div>

            {{-- Battery Card --}}
            <div class="card">
                <div class="card-header">
                    <h2>🔋 Sumber Daya & Baterai</h2>
                </div>
                @if (!empty($pc->battery['is_laptop']))
                    <div class="info-row">
                        <span class="info-label">Tipe Perangkat</span>
                        <span class="info-value"><strong>💻 Laptop Portabel</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Sisa Daya Baterai</span>
                        <span class="info-value">
                            <span class="status-badge {{ ($pc->battery['percent'] ?? 0) > 20 ? 'online' : 'offline' }}">
                                🔋 {{ $pc->battery['percent'] ?? '?' }}%
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status Pengisian</span>
                        <span class="info-value"><strong>{{ $pc->battery['status'] ?? 'Aktif' }}</strong></span>
                    </div>
                    @if (!empty($pc->battery['health_percent']))
                        <div class="info-row">
                            <span class="info-label">Kesehatan Baterai (Health)</span>
                            <span class="info-value">
                                <strong>{{ $pc->battery['health_percent'] }}%</strong>
                                <small class="muted">(Kapasitas maksimal vs rancangan pabrik)</small>
                            </span>
                        </div>
                    @endif
                @else
                    <div class="info-row">
                        <span class="info-label">Tipe Sumber Daya</span>
                        <span class="info-value">
                            <span class="status-badge" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;">
                                🔌 Desktop PC (Sumber Daya Listrik AC / Tanpa Baterai)
                            </span>
                        </span>
                    </div>
                @endif
            </div>

            {{-- Detail Memori (RAM) & Slot --}}
            <div class="card">
                <div class="card-header">
                    <h2>🧠 Detail Memori (RAM) & Keping Slot</h2>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Kapasitas RAM</span>
                    <span class="info-value"><strong>{{ $pc->ram_gb ?? '-' }} GB</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tipe & Kecepatan Utama</span>
                    <span class="info-value">
                        <span class="pc-meta-badge" style="background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 13px;">
                             {{ $pc->ram_type ?? 'RAM' }} {{ $pc->ram_speed ? '@ ' . $pc->ram_speed . ' MHz' : '' }}
                        </span>
                    </span>
                </div>

                @if (!empty($pc->ram_details))
                    <h3 style="font-size: 13px; color: var(--text-muted); margin: 18px 0 10px; text-transform: uppercase;">Keping RAM Terpasang di Slot:</h3>
                    @foreach ($pc->ram_details as $ramStick)
                        <div class="disk-box" style="margin-bottom: 8px;">
                            <div class="disk-header" style="margin-bottom: 4px;">
                                <span>
                                    ⚡ <strong>Slot {{ $ramStick['slot'] ?? 'DIMM' }}</strong> — {{ $ramStick['size_gb'] ?? '?' }} GB {{ $ramStick['type'] ?? 'RAM' }}
                                </span>
                                <span style="color: #2563eb; font-weight: 700; font-size: 12px;">
                                    {{ $ramStick['speed'] ?? '?' }} MHz
                                    @if (!empty($ramStick['clock']) && $ramStick['clock'] != $ramStick['speed'])
                                        <small class="muted" style="font-weight: 500;">(clock: {{ $ramStick['clock'] }} MHz)</small>
                                    @endif
                                </span>
                            </div>
                            <div class="disk-meta">
                                <span>Pabrikan: <strong>{{ $ramStick['manufacturer'] ?? '-' }}</strong></span>
                                <span>Part No: <code>{{ $ramStick['part_number'] ?? '-' }}</code></span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Storage Details --}}
            <div class="card">
                <div class="card-header">
                    <h2>💾 Penyimpanan (Disk Fisik & Partisi)</h2>
                </div>

                <h3 style="font-size: 13px; color: var(--text-muted); margin-bottom: 10px; text-transform: uppercase;">Drive Partisi:</h3>
                @forelse ($pc->disks['partitions'] ?? [] as $part)
                    @php
                        $total = $part['total_gb'] ?? 0;
                        $free = $part['free_gb'] ?? 0;
                        $used = $total - $free;
                        $usedPct = $total > 0 ? round(($used / $total) * 100) : 0;
                        $isLow = $total > 0 && ($free / $total) < 0.1;
                    @endphp
                    <div class="disk-box">
                        <div class="disk-header">
                            <span>📁 Drive {{ $part['drive'] }}</span>
                            <span>{{ $usedPct }}% Terpakai</span>
                        </div>
                        <div class="disk-progress-wrap">
                            <div class="disk-progress-bar {{ $isLow ? 'low' : '' }}" style="width: {{ $usedPct }}%;"></div>
                        </div>
                        <div class="disk-meta">
                            <span>Sisa Kosong: <strong>{{ $free }} GB</strong></span>
                            <span>Total Kapasitas: <strong>{{ $total }} GB</strong></span>
                        </div>
                    </div>
                @empty
                    <div class="muted">Tidak ada data partisi drive.</div>
                @endforelse

                <h3 style="font-size: 13px; color: var(--text-muted); margin: 18px 0 10px; text-transform: uppercase;">Disk Fisik Terpasang & Status Kesehatan:</h3>
                @forelse ($pc->disks['physical'] ?? [] as $disk)
                    <div class="disk-box" style="margin-bottom: 8px;">
                        <div class="disk-header" style="margin-bottom: 4px;">
                            <span>
                                {{ ($disk['bus_type'] ?? '') === 'NVMe' ? '⚡' : '💽' }}
                                <strong>{{ $disk['model'] ?? 'Disk' }}</strong>
                            </span>
                            <span class="status-badge {{ ($disk['health'] ?? 'Healthy') === 'Healthy' ? 'online' : 'offline' }}" style="font-size: 11px; padding: 2px 8px;">
                                {{ $disk['health'] ?? 'Healthy' }}
                            </span>
                        </div>
                        <div class="disk-meta">
                            <span>Tipe: <strong>{{ $disk['type'] ?? 'Storage' }} ({{ $disk['bus_type'] ?? 'SATA' }})</strong></span>
                            <span>Kapasitas Fisik: <strong>{{ $disk['size_gb'] ?? '?' }} GB</strong></span>
                        </div>
                    </div>
                @empty
                    <div class="muted">Tidak ada informasi disk fisik terdeteksi.</div>
                @endforelse
            </div>

            {{-- Network Details --}}
            <div class="card">
                <div class="card-header">
                    <h2>🌐 Jaringan & Konektivitas</h2>
                </div>
                <div class="info-row">
                    <span class="info-label">IP Address Lokal</span>
                    <span class="info-value">
                        <code class="copy-pill" onclick="copyText('{{ $pc->ip }}')">{{ $pc->ip ?? '-' }} 📋</code>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">MAC Address Hardware</span>
                    <span class="info-value">
                        <code class="copy-pill" onclick="copyText('{{ $pc->mac }}')">{{ $pc->mac ?? '-' }} 📋</code>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Laporan Lengkap Terakhir</span>
                    <span class="info-value">{{ $pc->last_report_at ? $pc->last_report_at->format('d F Y, H:i:s') : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cek-in Heartbeat Terakhir</span>
                    <span class="info-value">{{ $pc->last_poll_at ? $pc->last_poll_at->diffForHumans() : '-' }}</span>
                </div>
            </div>
        </div>

        {{-- Right Column (CRUD Alias & Pengaturan Kantor) --}}
        <div>
            {{-- Edit Alias & Lokasi Card --}}
            <div class="card" id="editSection" style="border: 2px solid #e0e7ff;">
                <div class="card-header">
                    <h2>🏷️ Atur Alias & Lokasi Kantor</h2>
                </div>
                <form method="POST" action="{{ route('pcs.update', $pc) }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="alias">Nama Alias PC</label>
                        <input type="text" id="alias" name="alias" value="{{ old('alias', $pc->alias) }}" placeholder="Contoh: PC Kasir 1 / PC Budi Finance">
                        <small class="muted">Nama panggilan yang mudah dikenali oleh tim IT / kantor.</small>
                    </div>

                    <div class="form-group">
                        <label for="stock_status">Kategori Stock</label>
                        <select id="stock_status" name="stock_status" style="width: 100%; padding: 10px 14px; font-size: 13px; border: 1px solid var(--border-color); border-radius: 8px; outline: none; background: #fff; font-family: inherit;">
                            <option value="digunakan" {{ old('stock_status', $pc->stock_status ?? 'digunakan') === 'digunakan' ? 'selected' : '' }}>
                                🏢 Stock Digunakan (Dipakai Karyawan / Operasional)
                            </option>
                            <option value="bebas" {{ old('stock_status', $pc->stock_status) === 'bebas' ? 'selected' : '' }}>
                                📦 Stock Bebas (Siaga / Belum Digunakan / Cadangan)
                            </option>
                        </select>
                        <small class="muted">Tentukan apakah PC ini sedang dipakai aktif atau ready stock bebas.</small>
                    </div>

                    <div class="form-group">
                        <label for="department">Divisi / Departemen</label>
                        <input type="text" id="department" name="department" value="{{ old('department', $pc->department) }}" placeholder="Contoh: Finance, Marketing, IT">
                    </div>

                    <div class="form-group">
                        <label for="location">Lokasi / Ruangan</label>
                        <input type="text" id="location" name="location" value="{{ old('location', $pc->location) }}" placeholder="Contoh: Lantai 2, Meja 05">
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan Inventaris</label>
                        <textarea id="notes" name="notes" rows="4" placeholder="Catatan servis, upgrade RAM, riwayat garansi...">{{ old('notes', $pc->notes) }}</textarea>
                    </div>

                    <button type="submit" class="btn" style="width: 100%; justify-content: center;">
                        💾 Simpan Data Alias & Lokasi
                    </button>
                </form>
            </div>

            {{-- Danger Zone: Hapus PC --}}
            <div class="card" style="border-color: #fee2e2;">
                <div class="card-header">
                    <h2 style="color: #b91c1c;">⚠️ Zona Pengelolaan</h2>
                </div>
                <p class="muted" style="margin-bottom: 14px; font-size: 13px;">
                    Jika PC ini sudah dihibahkan, rusak total, atau tidak digunakan lagi di kantor, Anda dapat menghapusnya dari daftar inventaris.
                </p>
                <form id="deleteForm" method="POST" action="{{ route('pcs.destroy', $pc) }}">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-danger" style="width: 100%; justify-content: center;" onclick="confirmDeletePc('{{ addslashes($pc->display_name) }}')">
                        🗑️ Hapus PC dari Inventaris
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function copyText(text) {
    if (!text || text === '-') return;
    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Tersalin ke clipboard!',
            text: text,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });
}

function confirmDeletePc(name) {
    Swal.fire({
        title: 'Hapus ' + name + '?',
        text: 'Data inventaris PC ini akan dihapus permanen dari sistem.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '🗑️ Ya, Hapus Sekarang',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteForm').submit();
        }
    });
}

function confirmSyncSingle(e, form, name) {
    e.preventDefault();
    Swal.fire({
        title: 'Sinkronkan ' + name + '?',
        text: 'Mengirimkan sinyal ke agen di PC ini untuk mengambil dan memperbarui data spesifikasi hardware terbarunya.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: '🔄 Sinkronkan Sekarang',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
    return false;
}

function openRemoteModal(name, ip, rdpUrl, radminUrl) {
    if (!ip || ip === '-') {
        Swal.fire({
            icon: 'warning',
            title: 'IP Address Tidak Terdeteksi',
            text: 'PC ini belum memiliki IP address yang valid untuk di-remote.',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    Swal.fire({
        title: '⚡ Remote Desktop: ' + name,
        html: `
            <div style="text-align: left; font-size: 13px; line-height: 1.5; color: #334155;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 14px; border-radius: 8px; margin-bottom: 14px;">
                    <div>Target IP: <strong style="font-family: monospace; font-size: 14px; color: #0f172a;">${ip}</strong></div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Pastikan Anda terhubung ke jaringan LAN kantor atau Radmin VPN yang sama.</div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 9px;">
                    <!-- Opsi 1: Radmin 1-Klik Launcher -->
                    <a href="${radminUrl}" class="swal2-confirm swal2-styled" style="display: block; margin: 0; text-decoration: none; background: #059669; color: #fff; padding: 11px 14px; border-radius: 8px; text-align: center; font-weight: 700; box-shadow: 0 2px 4px rgba(5,150,105,0.2);">
                        🚀 1-Klik Radmin Viewer (Download .bat)
                    </a>
                    <div style="font-size: 11px; color: #64748b; margin-top: -5px; margin-bottom: 3px; text-align: center;">
                        Klik file .bat yang terunduh, Radmin langsung terbuka & terhubung otomatis.
                    </div>

                    <!-- Opsi 2: Windows RDP Bawaan -->
                    <a href="${rdpUrl}" class="swal2-confirm swal2-styled" style="display: block; margin: 0; text-decoration: none; background: #2563eb; color: #fff; padding: 11px 14px; border-radius: 8px; text-align: center; font-weight: 700; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">
                        🖥️ Windows Remote Desktop (.rdp)
                    </a>
                    <div style="font-size: 11px; color: #64748b; margin-top: -5px; margin-bottom: 3px; text-align: center;">
                        Remote standar bawaan Windows (tanpa aplikasi Radmin).
                    </div>

                    <!-- Opsi 3: Buka Protokol radmin:// langsung -->
                    <a href="radmin://${ip}" class="swal2-confirm swal2-styled" style="display: block; margin: 0; text-decoration: none; background: #0d9488; color: #fff; padding: 10px 14px; border-radius: 8px; text-align: center; font-weight: 600; font-size: 12px;">
                        ⚡ Buka Browser Protocol (radmin://${ip})
                    </a>

                    <!-- Opsi 4: Salin IP -->
                    <button type="button" class="swal2-cancel swal2-styled" style="display: block; margin: 0; background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; padding: 9px 14px; border-radius: 8px; font-weight: 600; font-size: 12px;" onclick="copyRadminIp('${ip}')">
                        📋 Salin IP (${ip}) ke Clipboard
                    </button>
                </div>

                <div style="margin-top: 14px; padding: 10px 12px; background: #fefce8; border: 1px solid #fef08a; border-radius: 8px; font-size: 11px; color: #854d0e; line-height: 1.4;">
                    💡 <strong>Tips Langsung Buka:</strong> Jika tombol hijau <em>radmin://</em> tidak merespons, itu karena Windows belum mendaftarkan protokol Radmin di browser. Anda cukup jalankan file <code>pasang-protokol-radmin.bat</code> (klik kanan <em>Run as administrator</em>) sekali saja di PC admin ini.
                </div>
            </div>
        `,
        showConfirmButton: false,
        showCloseButton: true,
        focusConfirm: false
    });
}

function copyRadminIp(ip) {
    navigator.clipboard.writeText(ip).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'IP ' + ip + ' berhasil disalin!',
            showConfirmButton: false,
            timer: 2000
        });
    });
}
</script>
</body>
</html>

