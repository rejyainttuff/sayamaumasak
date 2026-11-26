<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password — Bali Culinary</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('Assets/css/recipes.css') }}?v={{ filemtime(public_path('Assets/css/recipes.css')) }}">
</head>

<body class="recipe-page">
    <div class="auth-page">
        <img src="{{ asset('Assets/images/decor-hibiscus.png') }}" alt="" class="corner-hibiscus top-left" aria-hidden="true">
        <img src="{{ asset('Assets/images/decor-hibiscus.png') }}" alt="" class="corner-hibiscus bottom-right" aria-hidden="true">

        <div class="auth-top-brand">
            <a href="{{ route('home') }}" class="bali-brand">Bali <span>Culinary</span></a>
        </div>

        <div class="auth-card">
            <h2 style="margin-bottom: 18px;">Ganti Password</h2>

            @if (session('success'))
                <div class="alert-banner success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert-banner error">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('password.change.submit') }}" method="POST">
                @csrf

                <div class="input-icon-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="4" width="20" height="16" rx="2" />
                        <path d="m22 7-10 6L2 7" />
                    </svg>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required>
                </div>

                <span class="auth-field-label">Buat Password</span>

                <div class="input-icon-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    <input type="password" name="password" placeholder="Password Baru" required>
                </div>

                <div class="input-icon-wrap" style="margin-bottom: 18px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    <input type="password" name="password_confirmation" placeholder="Konfirmasi Password" required>
                </div>

                <button type="submit" class="btn-terracotta rect" style="width: 100%; height: 42px; margin-bottom: 10px;">Ganti Password</button>
                <a href="{{ route('login') }}" class="btn-white" style="width: 100%; height: 42px;">Batal</a>
            </form>
        </div>
    </div>
    <script src="{{ asset('Assets/js/animations.js') }}" defer></script>
</body>

</html>
