<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Refresh lebih cepat (10 detik) jika ada PC yang sedang menunggu sinkronisasi --}}
    <meta http-equiv="refresh" content="{{ $pcs->contains('sync_pending', true) ? 10 : 60 }}">
    <title>Inventaris PC Kantor - Dashboard & Spesifikasi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-brand h1 {
            font-size: 19px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-tag {
            font-size: 11px;
            font-weight: 600;
            background: #1e293b;
            color: #94a3b8;
            padding: 3px 10px;
            border-radius: 9999px;
            border: 1px solid #334155;
            letter-spacing: 0.3px;
        }

        .wrap {
            max-width: 1480px;
            margin: 0 auto;
            padding: 24px 32px 48px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 18px 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .stat-card .label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .stat-card .val {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.1;
        }

        .stat-card.online .val { color: var(--success); }
        .stat-card.offline .val { color: var(--danger); }
        .stat-card.sync .val { color: #d97706; }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 420px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            font-size: 14px;
            border: 1px solid var(--border-color);
            border-radius: 9px;
            background: #ffffff;
            outline: none;
            transition: all 0.15s;
        }

        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
        }

        .card-table {
            background: var(--card-bg);
            border-radius: 14px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .scroll {
            overflow-x: auto;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            text-align: left;
            font-size: 13px;
        }

        thead tr {
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            padding: 13px 16px;
            font-weight: 600;
            color: #475569;
            white-space: nowrap;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #fbfcfe;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.online {
            background: var(--success-light);
            color: #15803d;
        }

        .status-badge.offline {
            background: var(--danger-light);
            color: #b91c1c;
        }

        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .stock-badge.used {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .stock-badge.free {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .stock-filter-tabs {
            display: inline-flex;
            background: #e2e8f0;
            padding: 3px;
            border-radius: 9px;
            gap: 2px;
        }

        .tab-btn {
            border: none;
            background: transparent;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            border-radius: 7px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .tab-btn:hover {
            color: #0f172a;
        }

        .tab-btn.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .sync-badge {
            display: inline-block;
            background: var(--warning-bg);
            color: var(--warning-text);
            border-radius: 6px;
            padding: 2px 7px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .pc-alias-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pc-alias {
            font-weight: 700;
            color: #0f172a;
            font-size: 15px;
        }

        .pc-hostname {
            color: #3b82f6;
            font-size: 13px;
            font-weight: 600;
            font-family: monospace;
        }

        .pc-meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            padding: 2px 7px;
            border-radius: 4px;
            background: #f1f5f9;
            color: #475569;
            margin-top: 4px;
        }

        .btn-edit-alias {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.15s;
        }

        .btn-edit-alias:hover {
            color: var(--primary);
            background: var(--primary-light);
        }

        .btn {
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: background 0.15s;
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
            color: var(--text-main);
        }

        .btn-outline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .btn-detail {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            font-weight: 600;
        }

        .btn-detail:hover {
            background: var(--primary-light);
            border-color: #93c5fd;
            color: var(--primary);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 7px;
        }

        .actions-group {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
        }

        .muted {
            color: var(--text-muted);
            font-size: 12px;
        }

        .disk-progress-wrap {
            background: #e2e8f0;
            height: 6px;
            width: 130px;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 3px;
        }

        .disk-progress-bar {
            height: 100%;
            background: #3b82f6;
            border-radius: 3px;
        }

        .disk-progress-bar.low {
            background: #ef4444;
        }

        .empty-state {
            padding: 56px 24px;
            text-align: center;
            color: var(--text-muted);
        }

        /* Modal styling */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(2px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
            padding: 16px;
        }

        .modal-card {
            background: #ffffff;
            width: 100%;
            max-width: 500px;
            border-radius: 14px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: modalFadeIn 0.2s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 16px;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-muted);
        }

        .modal-body {
            padding: 24px;
        }

        .form-group {
            margin-bottom: 16px;
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

        .modal-footer {
            padding: 14px 24px;
            border-top: 1px solid var(--border-color);
            background: #f8fafc;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
    </style>
</head>
<body>

<header>
    <div class="header-brand">
        <h1>
            <span>🖥️</span> Inventaris PC Kantor
        </h1>
        <span class="header-tag">Auto-Sync On Startup</span>
    </div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 13px; color: #94a3b8; margin-right: 4px;">
            👤 <strong style="color: #ffffff;">{{ auth()->user()->name ?? 'Admin IT' }}</strong>
        </span>
        <a href="{{ route('pcs.export') }}" class="btn btn-outline" style="color: #ffffff; border-color: #334155; background: #1e293b;" title="Download daftar inventaris PC dalam format spreadsheet">
            📥 Export CSV
        </a>
        <form id="syncAllForm" method="POST" action="{{ route('pcs.sync-all') }}" style="display: inline;">
            @csrf
            <button type="button" class="btn" onclick="confirmSyncAll()">
                🔄 Sinkron Semua
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

    {{-- Stats Cards --}}
    <div class="stats-grid">
        <div class="stat-card">
            <span class="label">Total Perangkat PC</span>
            <span class="val">{{ $pcs->count() }}</span>
        </div>
        <div class="stat-card" style="border-left: 4px solid #3b82f6;">
            <span class="label">🏢 Stock Digunakan</span>
            <span class="val" style="color: #2563eb;">{{ $pcs->where('stock_status', '!=', 'bebas')->count() }}</span>
        </div>
        <div class="stat-card" style="border-left: 4px solid #10b981;">
            <span class="label">📦 Stock Bebas</span>
            <span class="val" style="color: #059669;">{{ $pcs->where('stock_status', 'bebas')->count() }}</span>
        </div>
        {{-- <div class="stat-card" style="border-left: 4px solid #f59e0b; cursor: pointer;" onclick="document.getElementById('tabWarnings').click()" title="Klik untuk memfilter PC dengan peringatan">
            <span class="label">⚠️ Perlu Perhatian</span>
            <span class="val" style="color: #d97706;">{{ $pcs->filter->has_warnings->count() }}</span>
        </div> --}}
        <div class="stat-card online">
            <span class="label">PC Online (Aktif)</span>
            <span class="val">{{ $pcs->where('is_online', true)->count() }}</span>
        </div>
        <div class="stat-card offline">
            <span class="label">PC Offline (Mati)</span>
            <span class="val">{{ $pcs->where('is_online', false)->count() }}</span>
        </div>
        <div class="stat-card sync">
            <span class="label">Menunggu Sinkron</span>
            <span class="val">{{ $pcs->where('sync_pending', true)->count() }}</span>
        </div>
    </div>

    {{-- Toolbar & Search --}}
    <div class="toolbar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input id="q" placeholder="Cari nama alias, hostname, IP, user, CPU, serial..." onkeyup="filterRows()">
            </div>
            <div class="stock-filter-tabs">
                <button type="button" class="tab-btn active" onclick="setStockFilter('all', this)">
                    Semua ({{ $pcs->count() }})
                </button>
                <button type="button" class="tab-btn" onclick="setStockFilter('digunakan', this)">
                    🏢 Digunakan ({{ $pcs->where('stock_status', '!=', 'bebas')->count() }})
                </button>
                <button type="button" class="tab-btn" onclick="setStockFilter('bebas', this)">
                    📦 Stock Bebas ({{ $pcs->where('stock_status', 'bebas')->count() }})
                </button>
                <button type="button" id="tabWarnings" class="tab-btn" onclick="setStockFilter('warning', this)" style="color: #b45309;">
                    ⚠️ Peringatan ({{ $pcs->filter->has_warnings->count() }})
                </button>
            </div>
        </div>
        <div class="muted">
            Auto-refresh setiap <strong>{{ $pcs->contains('sync_pending', true) ? '10' : '60' }} detik</strong>.
        </div>
    </div>

    {{-- PC Table --}}
    <div class="card-table">
        <div class="scroll">
            <table id="pc-table">
                <thead>
                    <tr>
                        <th style="width: 100px;">Status</th>
                        <th>Nama PC / Alias & Hostname</th>
                        <th>Pengguna & IP</th>
                        <th>Merk & Model</th>
                        <th>Processor (CPU)</th>
                        <th>RAM</th>
                        <th>Laporan Terakhir</th>
                        <th style="text-align: right; min-width: 190px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pcs as $p)
                        <tr data-stock="{{ $p->stock_status ?? 'digunakan' }}" data-warning="{{ $p->has_warnings ? 'yes' : 'no' }}">
                            {{-- Status Online/Offline & Kategori Stock --}}
                            <td>
                                <div>
                                    @if ($p->is_online)
                                        <span class="status-badge online">● Online</span>
                                    @else
                                        <span class="status-badge offline">○ Offline</span>
                                    @endif
                                </div>

                                <div style="margin-top: 4px;">
                                    @if ($p->stock_status === 'bebas')
                                        <span class="stock-badge free" title="Stock Bebas / Cadangan / Siaga">📦 Bebas</span>
                                    @else
                                        <span class="stock-badge used" title="Stock Sedang Digunakan Karyawan">🏢 Digunakan</span>
                                    @endif
                                </div>

                                @if ($p->sync_pending)
                                    <div><span class="sync-badge">⏳ Menunggu...</span></div>
                                @endif

                                @if ($p->recent_hardware_change)
                                    <div style="margin-top: 4px;">
                                        <span class="stock-badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a;" title="{{ $p->recent_hardware_change->description }}">
                                            ⚡ HW Berubah
                                        </span>
                                    </div>
                                @endif
                            </td>

                            {{-- Nama PC / Alias (Depan) --}}
                            <td>
                                <div class="pc-alias-wrap">
                                    @if ($p->alias)
                                        <span class="pc-alias">{{ $p->alias }}</span>
                                    @else
                                        <span class="pc-alias" style="color: #64748b; font-style: italic; font-weight: 500;">(Belum ada alias)</span>
                                    @endif
                                    <button type="button" class="btn-edit-alias" title="Ubah Nama Alias / Departemen / Kategori Stock" 
                                            data-id="{{ $p->id }}"
                                            data-alias="{{ $p->alias }}"
                                            data-stock="{{ $p->stock_status ?? 'digunakan' }}"
                                            data-dept="{{ $p->department }}"
                                            data-loc="{{ $p->location }}"
                                            data-uvid="{{ $p->ultraviewer_id }}"
                                            data-uvpass="{{ $p->ultraviewer_pass }}"
                                            data-notes="{{ $p->notes }}"
                                            onclick="openEditModalFromButton(this)">
                                        ✏️
                                    </button>
                                </div>
                                <div>
                                    <span class="pc-hostname">{{ $p->hostname }}</span>
                                </div>
                                @if ($p->department || $p->location)
                                    <div class="pc-meta-badge">
                                        🏢 {{ $p->department ?: 'Umum' }} {{ $p->location ? '• '.$p->location : '' }}
                                    </div>
                                @endif

                                {{-- Early Warning Alerts --}}
                                @if ($p->has_warnings)
                                    <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 2px;">
                                        @foreach ($p->warnings as $w)
                                            <div style="font-size: 11px; font-weight: 700; color: {{ $w['severity'] === 'danger' ? '#b91c1c' : '#b45309' }}; display: flex; align-items: center; gap: 4px;">
                                                <span>⚠️</span>
                                                <span>{{ $w['message'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            {{-- User & IP --}}
                            <td>
                                <div><strong>{{ $p->login_user ?: '-' }}</strong></div>
                                <div class="muted">IP: <code>{{ $p->ip ?? '-' }}</code></div>
                                @if ($p->ultraviewer_id)
                                    <div style="margin-top: 3px; font-size: 11px; color: #0284c7; font-weight: 700;" title="UltraViewer ID">
                                        ⚡ UV: <code style="font-family: monospace; background: #e0f2fe; color: #0369a1; padding: 1px 4px; border-radius: 4px;">{{ $p->ultraviewer_formatted_id }}</code>
                                    </div>
                                @endif
                            </td>

                            {{-- Merk & Model --}}
                            <td>
                                <div>{{ $p->manufacturer ?? '-' }} {{ $p->model ?? '' }}</div>
                                <div class="muted">SN: {{ $p->serial ?: '-' }}</div>
                            </td>

                            {{-- CPU & GPU --}}
                            <td>
                                <div style="font-weight: 500;">{{ $p->cpu ?? '-' }}</div>
                                <div class="muted">{{ $p->cpu_cores ?? '?' }} Cores / {{ $p->cpu_threads ?? '?' }} Threads</div>
                                @if ($p->gpu)
                                    <div style="font-size: 11px; margin-top: 3px; color: #475569; display: flex; align-items: center; gap: 4px;" title="Kartu Grafis">
                                        <span>🎮</span>
                                        <span style="font-weight: 600; color: #1e293b;">{{ Str::limit($p->gpu, 30) }}</span>
                                    </div>
                                @endif
                            </td>

                            {{-- RAM --}}
                            <td>
                                <div><strong>{{ $p->ram_gb ?? '-' }} GB</strong></div>
                            </td>

                            {{-- Laporan Terakhir --}}
                            <td>
                                <div>{{ $p->last_report_at ? $p->last_report_at->format('d/m/Y H:i') : '-' }}</div>
                                <div class="muted">{{ $p->last_poll_at ? 'Poll '.$p->last_poll_at->diffForHumans() : '-' }}</div>
                            </td>

                            {{-- Tombol Aksi: Remote, Print Label, Detail, Sinkron --}}
                            <td>
                                <div class="actions-group">
                                    {{-- Remote PC --}}
                                    <button type="button" class="btn btn-outline btn-sm" style="color: #0284c7; border-color: #bae6fd; background: #f0f9ff; font-weight: 700;" title="Remote Desktop PC (UltraViewer / RDP / Radmin)" onclick="openRemoteModal('{{ addslashes($p->display_name) }}', '{{ $p->ip }}', '{{ route('pcs.remote-rdp', $p) }}', '{{ route('pcs.remote-radmin', $p) }}', '{{ $p->ultraviewer_id }}', '{{ $p->ultraviewer_password }}', '{{ route('pcs.remote-ultraviewer', $p) }}', '{{ route('pcs.ultraviewer-pass', $p) }}', '{{ route('pcs.remote-rdp-shadow', $p) }}')">
                                        ⚡ Remote
                                    </button>

                                    {{-- Cetak Stiker Label QR --}}
                                    <a href="{{ route('pcs.print-label', $p) }}" target="_blank" class="btn btn-outline btn-sm" title="Cetak Stiker Label QR Aset">
                                        🖨️
                                    </a>

                                    {{-- Detail Spesifikasi --}}
                                    <a href="{{ route('pcs.show', $p) }}" class="btn btn-detail btn-sm" title="Lihat Spesifikasi Lengkap">
                                        🔍
                                    </a>

                                    {{-- Sinkron --}}
                                    <form method="POST" action="{{ route('pcs.sync', $p) }}" style="display: inline;" onsubmit="return confirmSyncSingle(event, this, '{{ addslashes($p->display_name) }}')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm" @disabled($p->sync_pending) title="Sinkronkan Data PC Ini">
                                            {{ $p->sync_pending ? '⏳' : '🔄' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <h3>Belum ada PC kantor terdaftar</h3>
                                    <p class="muted" style="margin-top: 6px;">Jalankan <code>agent.py</code> pada PC kantor untuk mendaftarkan spesifikasi secara otomatis.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Edit Alias / Metadata --}}
<div id="editModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>✏️ Atur Alias & Lokasi PC</h3>
            <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="editForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label for="modalAlias">Nama Alias PC</label>
                    <input type="text" id="modalAlias" name="alias" placeholder="Contoh: PC Kasir 1 / PC Desain Grafis">
                    <small class="muted">Nama alias ramah pengguna yang tampil di dashboard kantor.</small>
                </div>
                <div class="form-group">
                    <label for="modalStockStatus">Kategori Stock</label>
                    <select id="modalStockStatus" name="stock_status" style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: 8px; outline: none; background: #fff; font-family: inherit;">
                        <option value="digunakan">🏢 Stock Digunakan (Dipakai Karyawan / Operasional)</option>
                        <option value="bebas">📦 Stock Bebas (Siaga / Belum Digunakan / Cadangan)</option>
                    </select>
                    <small class="muted">Tentukan apakah PC ini sedang dipakai aktif atau ready stock cadangan.</small>
                </div>
                <div class="form-group">
                    <label for="modalDept">Divisi / Departemen</label>
                    <input type="text" id="modalDept" name="department" placeholder="Contoh: Finance, HRD, IT, Gudang">
                </div>
                <div class="form-group">
                    <label for="modalLocation">Lokasi Ruangan / Meja</label>
                    <input type="text" id="modalLocation" name="location" placeholder="Contoh: Lantai 2, Ruang Admin, Meja 04">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="modalUltraviewerId">UltraViewer ID</label>
                        <input type="text" id="modalUltraviewerId" name="ultraviewer_id" placeholder="Otomatis terbaca dari PC">
                        <small class="muted">Diambil otomatis oleh agent atau diisi manual.</small>
                    </div>
                    <div class="form-group">
                        <label for="modalUltraviewerPass">Password Remote</label>
                        <input type="text" id="modalUltraviewerPass" name="ultraviewer_pass" placeholder="Default: pckantor">
                        <small class="muted">Password permanen/custom di UltraViewer.</small>
                    </div>
                </div>
                <div class="form-group">
                    <label for="modalNotes">Catatan Tambahan</label>
                    <textarea id="modalNotes" name="notes" rows="3" placeholder="Contoh: Pengadaan 2024, user utama Pak Budi"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentStockFilter = 'all';

function setStockFilter(stock, btn) {
    currentStockFilter = stock;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

function filterRows() {
    applyFilters();
}

function applyFilters() {
    const q = document.getElementById('q').value.toLowerCase();
    const rows = document.querySelectorAll('#pc-table tbody tr');
    rows.forEach(r => {
        // Skip empty row if any
        if (r.children.length <= 1) return;
        const textMatch = r.innerText.toLowerCase().includes(q);
        const rowStock = r.getAttribute('data-stock') || 'digunakan';
        const isWarning = r.getAttribute('data-warning') === 'yes';

        let categoryMatch = false;
        if (currentStockFilter === 'all') {
            categoryMatch = true;
        } else if (currentStockFilter === 'warning') {
            categoryMatch = isWarning;
        } else {
            categoryMatch = (rowStock === currentStockFilter);
        }

        r.style.display = (textMatch && categoryMatch) ? '' : 'none';
    });
}

function formatUvId(id) {
    if (!id) return '-';
    return String(id).replace(/\s+/g, '').replace(/(\d{3})(?=\d)/g, '$1 ');
}

function launchUltraviewer(baseUrl) {
    const passInput = document.getElementById('swalUvPass');
    const pass = passInput ? encodeURIComponent(passInput.value.trim()) : 'pckantor';
    const separator = baseUrl.includes('?') ? '&' : '?';
    window.location.href = baseUrl + separator + 'pass=' + pass;
}

function saveUvPassword(url) {
    const passInput = document.getElementById('swalUvPass');
    const pass = passInput ? passInput.value.trim() : 'pckantor';
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ ultraviewer_pass: pass })
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.ok) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Password berhasil disimpan!',
                showConfirmButton: false,
                timer: 2000
            });
        }
    })
    .catch(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'Gagal menyimpan password',
            showConfirmButton: false,
            timer: 2000
        });
    });
}

function copyText(text, label) {
    if (!text || text === '-') return;
    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: (label || 'Teks') + ' berhasil disalin!',
            showConfirmButton: false,
            timer: 2000
        });
    });
}

function copyRadminIp(ip) {
    copyText(ip, 'IP Target');
}

function openRemoteModal(name, ip, rdpUrl, radminUrl, uvId, uvPass, uvDownloadUrl, uvPassUpdateUrl, rdpShadowUrl) {
    Swal.fire({
        title: '⚡ Remote Desktop: ' + name,
        html: `
            <div style="text-align: left; font-size: 13px; line-height: 1.5; color: #334155;">
                <!-- Bagian 1: ULTRAVIEWER (HERO / UTAMA KANTOR) -->
                <div style="background: #f0f9ff; border: 1.5px solid #bae6fd; padding: 14px 16px; border-radius: 10px; margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="font-weight: 800; font-size: 14px; color: #0369a1; display: flex; align-items: center; gap: 6px;">
                            <span>⚡</span>
                            <span>UltraViewer (Layar Bersama & Gratis)</span>
                        </div>
                        <span style="font-size: 11px; background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 99px; font-weight: 700;">Rekomendasi Kantor</span>
                    </div>

                    <!-- UltraViewer ID Box -->
                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 600;">PARTNER ID TARGET:</div>
                            <div style="font-family: monospace; font-size: 18px; font-weight: 800; color: #0f172a; letter-spacing: 1px;">
                                ${uvId ? formatUvId(uvId) : '<span style="color: #ef4444; font-size: 13px; font-weight: 600;">Belum Terdeteksi</span>'}
                            </div>
                        </div>
                        ${uvId ? `<button type="button" onclick="copyText('${uvId}', 'UltraViewer ID')" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #334155; padding: 6px 12px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer;">📋 Salin ID</button>` : ''}
                    </div>

                    <!-- UltraViewer Password Input -->
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">PASSWORD REMOTE (Default: pckantor):</label>
                        <div style="display: flex; gap: 6px;">
                            <input type="text" id="swalUvPass" value="${uvPass || 'pckantor'}" style="flex: 1; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: monospace; font-size: 13px; font-weight: 700; color: #0f172a;" placeholder="Ketik password custom client...">
                            <button type="button" onclick="saveUvPassword('${uvPassUpdateUrl}')" style="background: #0284c7; color: #fff; border: none; padding: 8px 12px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer;" title="Simpan password ini untuk PC ini">💾 Simpan</button>
                            <button type="button" onclick="copyText(document.getElementById('swalUvPass').value, 'Password UltraViewer')" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #334155; padding: 8px 10px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer;" title="Salin Password">📋</button>
                        </div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                            💡 <em>Ubah password jika user memberi tahu password acak hari ini, atau gunakan password default kantor.</em>
                        </div>
                    </div>

                    <!-- 1-Click Launch UltraViewer Button -->
                    ${uvId ? `
                        <button type="button" onclick="launchUltraviewer('${uvDownloadUrl}')" class="swal2-confirm swal2-styled" style="display: block; width: 100%; margin: 0; text-decoration: none; background: #0284c7; color: #fff; padding: 12px 14px; border-radius: 8px; text-align: center; font-weight: 800; font-size: 14px; box-shadow: 0 4px 6px -1px rgba(2,132,199,0.3); border: none; cursor: pointer;">
                            🚀 1-Klik Buka UltraViewer (Otomatis Sambung)
                        </button>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px; text-align: center;">
                            Klik tombol di atas untuk mengunduh shortcut launcher yang otomatis membuka UltraViewer & menghubungkan ke target.
                        </div>
                    ` : `
                        <div style="background: #fffbeb; border: 1px solid #fef08a; padding: 8px 10px; border-radius: 6px; font-size: 11px; color: #92400e;">
                            ⚠️ ID UltraViewer belum terbaca otomatis. Pastikan aplikasi UltraViewer sudah terpasang di komputer <strong>${name}</strong>. Anda juga bisa mengisi ID secara manual lewat tombol ✏️ Edit.
                        </div>
                    `}
                </div>

                <!-- Bagian 2: OPSI REMOTE LAINNYA (RDP SHADOW / RDP / RADMIN) -->
                <details style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 12px;">
                    <summary style="font-weight: 700; color: #475569; cursor: pointer; user-select: none;">
                        ⚙️ Opsi Remote Alternatif (RDP Shadow / RDP Standar / Radmin)
                    </summary>
                    <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px;">
                        <div style="font-size: 11px; color: #64748b;">Target IP Jaringan: <code style="font-family: monospace; font-weight: 700; color: #0f172a;">${ip || '-'}</code></div>

                        <!-- Opsi RDP Shadow -->
                        <a href="${rdpShadowUrl}" class="swal2-confirm swal2-styled" style="display: block; margin: 0; text-decoration: none; background: #4f46e5; color: #fff; padding: 10px 12px; border-radius: 6px; text-align: center; font-weight: 700; font-size: 12px; box-shadow: 0 2px 4px rgba(79,70,229,0.2);">
                            🖥️ 1-Klik RDP Shadow (.bat - Layar Bersama Tanpa Kunci)
                        </a>
                        <div style="font-size: 10px; color: #64748b; margin-top: -5px; text-align: center;">
                            Bawaan resmi Windows: Monitor fisik karyawan tetap menyala & tidak terkunci.
                        </div>
                        
                        <!-- Opsi RDP Standar -->
                        <a href="${rdpUrl}" class="swal2-confirm swal2-styled" style="display: block; margin: 0; text-decoration: none; background: #2563eb; color: #fff; padding: 9px 12px; border-radius: 6px; text-align: center; font-weight: 700; font-size: 12px;">
                            🖥️ Download Windows Remote Desktop Standar (.rdp)
                        </a>

                        <!-- Opsi Radmin -->
                        <a href="${radminUrl}" class="swal2-confirm swal2-styled" style="display: block; margin: 0; text-decoration: none; background: #059669; color: #fff; padding: 9px 12px; border-radius: 6px; text-align: center; font-weight: 700; font-size: 12px;">
                            🚀 Download Radmin Viewer (.bat)
                        </a>

                        <button type="button" class="swal2-cancel swal2-styled" style="display: block; margin: 0; background: #ffffff; color: #334155; border: 1px solid #cbd5e1; padding: 8px 12px; border-radius: 6px; font-weight: 600; font-size: 11px; cursor: pointer;" onclick="copyText('${ip}', 'IP Target')">
                            📋 Salin IP Target (${ip})
                        </button>
                    </div>
                </details>
            </div>
        `,
        showConfirmButton: false,
        showCloseButton: true,
        focusConfirm: false
    });
}

function openEditModalFromButton(btn) {
    const id = btn.getAttribute('data-id') || '';
    const alias = btn.getAttribute('data-alias') || '';
    const stock = btn.getAttribute('data-stock') || 'digunakan';
    const dept = btn.getAttribute('data-dept') || '';
    const loc = btn.getAttribute('data-loc') || '';
    const uvid = btn.getAttribute('data-uvid') || '';
    const uvpass = btn.getAttribute('data-uvpass') || '';
    const notes = btn.getAttribute('data-notes') || '';
    openEditModal(id, alias, stock, dept, loc, uvid, uvpass, notes);
}

function openEditModal(id, alias, stock, dept, loc, uvid, uvpass, notes) {
    document.getElementById('editForm').action = '/pcs/' + id;
    document.getElementById('modalAlias').value = alias;
    document.getElementById('modalStockStatus').value = stock || 'digunakan';
    document.getElementById('modalDept').value = dept;
    document.getElementById('modalLocation').value = loc;
    document.getElementById('modalUltraviewerId').value = uvid || '';
    document.getElementById('modalUltraviewerPass').value = uvpass || '';
    document.getElementById('modalNotes').value = notes;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function confirmSyncAll() {
    Swal.fire({
        title: 'Sinkronkan Semua PC?',
        text: 'Sinyal pembaruan spesifikasi akan dikirim ke seluruh PC kantor. PC yang online akan merespons dalam ±30 detik.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: '🔄 Ya, Sinkronkan Semua',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('syncAllForm').submit();
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

// Tutup modal jika klik di luar kartu
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
}
</script>
</body>
</html>
