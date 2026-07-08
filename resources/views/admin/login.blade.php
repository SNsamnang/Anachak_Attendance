<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — QR Check-In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .login-wrap {
            width: 100%;
            max-width: 400px;
        }
        .brand {
            text-align: center;
            margin-bottom: 28px;
        }
        .brand-icon {
            width: 56px; height: 56px;
            background: #10b981;
            border-radius: 16px;
            display: inline-grid;
            place-items: center;
            font-size: 26px;
            margin-bottom: 14px;
        }
        .brand h1 {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 4px;
        }
        .brand p {
            color: #64748b;
            font-size: 13.5px;
            margin: 0;
        }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 28px;
        }
        .form-label {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }
        .form-control {
            background: #0f172a;
            border: 1px solid #334155;
            color: #f1f5f9;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
        }
        .form-control::placeholder { color: #475569; }
        .form-control:focus {
            background: #0f172a;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,.2);
            color: #f1f5f9;
        }
        .form-control.is-invalid { border-color: #ef4444; }
        .input-group-text {
            background: #0f172a;
            border: 1px solid #334155;
            border-right: none;
            color: #64748b;
            border-radius: 10px 0 0 10px;
        }
        .input-group .form-control { border-left: none; border-radius: 0 10px 10px 0; }
        .btn-toggle {
            background: #0f172a;
            border: 1px solid #334155;
            border-left: none;
            color: #64748b;
            border-radius: 0 10px 10px 0;
            padding: 0 14px;
            cursor: pointer;
        }
        .btn-toggle:hover { color: #94a3b8; }
        .btn-primary {
            background: #10b981;
            border: none;
            border-radius: 10px;
            padding: 11px;
            font-size: 14px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: background .18s;
        }
        .btn-primary:hover { background: #059669; }
        .alert-danger {
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.3);
            color: #fca5a5;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
        }
        .divider {
            text-align: center;
            color: #334155;
            font-size: 12px;
            margin: 20px 0;
            position: relative;
        }
        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 44%;
            height: 1px;
            background: #334155;
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }
        .portal-link {
            text-align: center;
            font-size: 13px;
            color: #64748b;
        }
        .portal-link a { color: #10b981; text-decoration: none; }
        .portal-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="brand">
        <div class="brand-icon">📋</div>
        <h1>Admin Login</h1>
        <p>QR Check-In System</p>
    </div>

    <div class="card">
        @if($errors->any())
        <div class="alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                        placeholder="admin@example.com"
                        autofocus required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="pwdField"
                        class="form-control" placeholder="••••••••" required>
                    <button type="button" class="btn-toggle" id="togglePwd">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-primary">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="divider">or</div>
        <div class="portal-link">
            Employee? <a href="{{ route('portal.login') }}">Go to Employee Portal →</a>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePwd').addEventListener('click', function() {
    const f = document.getElementById('pwdField');
    const i = document.getElementById('eyeIcon');
    const show = f.type === 'password';
    f.type = show ? 'text' : 'password';
    i.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
});
</script>
</body>
</html>
