<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Label Stiker Aset - {{ $pc->display_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="{{ asset('vendor/qrcode/qrcode.min.js') }}"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .screen-toolbar {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.15s;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-outline {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .btn-outline:hover {
            background: #f8fafc;
        }

        /* Container Label Aset */
        .label-container {
            width: 90mm;
            height: 55mm;
            background: #ffffff;
            border: 2px dashed #94a3b8;
            border-radius: 8px;
            padding: 12px 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .label-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 5px;
            margin-bottom: 6px;
        }

        .label-header-title {
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .label-header-badge {
            font-size: 9px;
            font-weight: 800;
            background: #0f172a;
            color: #ffffff;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .label-content {
            display: flex;
            gap: 12px;
            flex: 1;
            align-items: center;
        }

        .label-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .label-alias {
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.15;
            word-break: break-word;
        }

        .label-hostname {
            font-size: 11px;
            font-weight: 700;
            color: #2563eb;
            font-family: monospace;
        }

        .label-meta {
            font-size: 10px;
            color: #334155;
            font-weight: 600;
            line-height: 1.3;
            margin-top: 2px;
        }

        .label-spec {
            font-size: 9px;
            color: #64748b;
            margin-top: 3px;
            border-top: 1px dotted #cbd5e1;
            padding-top: 3px;
        }

        .label-qr-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-width: 100px;
        }

        .label-qr-box {
            background: #ffffff;
            padding: 4px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        .label-qr-box img, .label-qr-box canvas {
            display: block;
            width: 82px !important;
            height: 82px !important;
        }

        .label-qr-text {
            font-size: 8px;
            font-weight: 700;
            color: #64748b;
            margin-top: 3px;
            letter-spacing: 0.2px;
        }

        .label-footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            font-size: 8px;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
                margin: 0;
            }

            .screen-toolbar {
                display: none !important;
            }

            .label-container {
                border: 1px solid #000000;
                box-shadow: none;
                margin: 0;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

<div class="screen-toolbar">
    <a href="{{ route('pcs.show', $pc) }}" class="btn btn-outline">
        ⬅️ Kembali ke Detail PC
    </a>
    <button type="button" class="btn btn-primary" onclick="window.print()">
        🖨️ Cetak Stiker Label (Print)
    </button>
    <span style="font-size: 12px; color: #64748b;">
        Ukuran standar stiker aset: <strong>90 × 55 mm</strong> (bisa disesuaikan pada opsi printer).
    </span>
</div>

<div class="label-container" id="printableLabel">
    <div class="label-header">
        <span class="label-header-title">
            🏢 ASET INVENTARIS IT KANTOR
        </span>
        <span class="label-header-badge">
            {{ $pc->stock_status === 'bebas' ? 'STOCK BEBAS' : 'DIGUNAKAN' }}
        </span>
    </div>

    <div class="label-content">
        <div class="label-info">
            <div class="label-alias">{{ $pc->alias ?: $pc->hostname }}</div>
            <div class="label-hostname">Host: {{ $pc->hostname }}</div>
            
            <div class="label-meta">
                <div>Divisi: <strong>{{ $pc->department ?: '-' }}</strong></div>
                <div>Lokasi: <strong>{{ $pc->location ?: '-' }}</strong></div>
                <div>User: <strong>{{ $pc->login_user ?: '-' }}</strong></div>
            </div>

            <div class="label-spec">
                <div>CPU: {{ Str::limit($pc->cpu, 28) }}</div>
                <div>RAM: {{ $pc->ram_gb ?? '-' }} GB {{ $pc->ram_type ?? '' }} • IP: {{ $pc->ip ?? '-' }}</div>
            </div>
        </div>

        <div class="label-qr-wrap">
            <div class="label-qr-box" id="qrcode"></div>
            <div class="label-qr-text">SCAN CEK SPEK</div>
        </div>
    </div>

    <div class="label-footer">
        <span>SN: {{ $pc->serial ?: '-' }}</span>
        <span>UID: {{ Str::limit($pc->uid, 18) }}</span>
        <span>Dicetak: {{ now()->format('d/m/Y') }}</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailUrl = "{{ route('pcs.show', $pc) }}";
    new QRCode(document.getElementById("qrcode"), {
        text: detailUrl,
        width: 82,
        height: 82,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.M
    });
});
</script>

</body>
</html>

