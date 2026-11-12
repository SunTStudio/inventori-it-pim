<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Inventaris PC Kantor</title>
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
            --bg-main: #0f172a;
            --card-bg: #ffffff;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            background: #1e293b;
            color: #ffffff;
            padding: 32px 32px 28px;
            text-align: center;
            border-bottom: 1px solid #334155;
        }

        .login-logo {
            font-size: 46px;
            margin-bottom: 12px;
            display: inline-block;
        }

        .login-header h1 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        .login-header p {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 6px;
        }

        .login-body {
            padding: 32px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #334155;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            font-size: 14px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            outline: none;
            transition: all 0.2s;
            font-family: inherit;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px 20px;
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .remember-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .hint-box {
            margin-top: 24px;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            font-size: 12px;
            color: #475569;
        }

        .hint-box strong {
            color: #0f172a;
        }

        .hint-box code {
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: 600;
            color: #1e293b;
        }

        .error-msg {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <span class="login-logo">🖥️</span>
        <h1>Inventaris PC Kantor</h1>
        <p>Masuk untuk mengelola spesifikasi & aset perangkat</p>
    </div>

    <div class="login-body">
        @if ($errors->any())
            <div class="error-msg">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email / Akun Admin</label>
                <input type="text" id="email" name="email" value="{{ old('email', 'admin@kantor.local') }}" required autofocus placeholder="admin@kantor.local">
            </div>

            <div class="form-group">
                <label for="password">Kata Sandi (Password)</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>

            <div class="remember-wrap">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span>Ingat saya di perangkat ini</span>
                </label>
            </div>

            <button type="submit" class="btn">
                🔐 Masuk ke Dashboard
            </button>
        </form>

        <div class="hint-box">
            <div>💡 <strong>Akun Admin Default:</strong></div>
            <div style="margin-top: 4px;">Email: <code>admin@kantor.local</code></div>
            <div>Password: <code>admin123</code></div>
        </div>
    </div>
</div>

@if (session('status'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: '{{ addslashes(session('status')) }}',
                showConfirmButton: false,
                timer: 3500
            });
        });
    </script>
@endif

</body>
</html>

