<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f7f4ef;
            color: #1f1f1f;
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 16px;
        }
        .login-card {
            width: 100%;
            max-width: 380px;
            background: #ffffff;
            border: 1px solid #e5dfd5;
            border-radius: 14px;
            padding: 24px;
            box-sizing: border-box;
        }
        .login-card h1 {
            margin: 0 0 6px;
            font-size: 22px;
        }
        .login-card p {
            margin: 0 0 18px;
            font-size: 14px;
            color: #666;
        }
        .form-group {
            margin-bottom: 14px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cfc8bc;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .btn-submit {
            width: 100%;
            padding: 10px 14px;
            border: 0;
            border-radius: 8px;
            background: #c85a32;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-submit:hover {
            background: #b04b26;
        }
        .error-box {
            background: #fdeceb;
            color: #9e1c1c;
            border: 1px solid #f5c2c0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
            margin-bottom: 14px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Masuk</h1>
        <p>Silakan masuk menggunakan akun Anda.</p>

        @if ($errors->any())
            <div class="error-box">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                >
            </div>

            <label class="remember-row">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>Ingat saya</span>
            </label>

            <button type="submit" class="btn-submit">Masuk</button>
        </form>
    </div>
</body>
</html>