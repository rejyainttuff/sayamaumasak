<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Akun — Bali Culinary</title>
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

        <div class="auth-split-shell">
            <div class="auth-intro">
                <h1>Nikmati kuliner Bali<br>dengan caramu sendiri.</h1>
                <p>Simpan resep favoritmu, bagikan racikan dapur keluarga, dan jelajahi kelezatan autentik dari seluruh penjuru Pulau Dewata.</p>
            </div>

            <div class="auth-card">
                <h2>Login Akun</h2>
                <p class="auth-card-sub">Masuk ke akunmu terlebih dahulu</p>

                @if (session('success'))
                    <div class="alert-banner success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert-banner error">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('login.submit') }}" method="POST">
                    @csrf

                    <div class="input-icon-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="4" width="20" height="16" rx="2" />
                            <path d="m22 7-10 6L2 7" />
                        </svg>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required autocomplete="email">
                    </div>

                    <div class="input-icon-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
                    </div>

                    <div class="auth-row-between">
                        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" name="remember">
                            <span>Ingat saya</span>
                        </label>
                        <a href="{{ route('password.change') }}">Lupa password?</a>
                    </div>

                    <button type="submit" class="btn-terracotta rect" style="width: 100%; height: 42px;">Masuk</button>

                    <div class="auth-divider">atau</div>

                    <a href="{{ route('register') }}" class="btn-white" style="width: 100%; height: 42px;">Buat Akun</a>
                </form>
            </div>
        </div>
    </div>
    <script src="{{ asset('Assets/js/animations.js') }}" defer></script>
</body>

</html>
