<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Bali Culinary</title>
    <link rel="icon" type="image/png" href="{{ asset('Assets/images/baliculinarylogo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('Assets/css/recipes.css') }}?v={{ filemtime(public_path('Assets/css/recipes.css')) }}">
</head>

<body class="recipe-page">
    <div class="auth-page">
        <div class="auth-top-brand">
            <a href="{{ route('home') }}" class="bali-brand brand-with-logo">
                <img src="{{ asset('Assets/images/baliculinarylogo.png') }}" alt="Bali Culinary" class="brand-logo-img auth-logo-img">
                <span>Bali <em style="font-style: normal; color: var(--terracotta);">Culinary</em></span>
            </a>
        </div>

        <div class="auth-card">
            <h2 style="margin-bottom: 6px;">Reset Password</h2>
            <p class="auth-card-sub">Masukkan email terdaftar beserta password baru untuk akun Anda.</p>

            @if (session('success'))
                <div class="alert-banner success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert-banner error">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('password.change.submit') }}" method="POST" data-loading-form>
                @csrf

                <div class="input-icon-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="4" width="20" height="16" rx="2" />
                        <path d="m22 7-10 6L2 7" />
                    </svg>
                    <input type="email" name="email" value="{{ old('email', $email ?? '') }}" placeholder="Email terdaftar" required autocomplete="email">
                </div>

                <div class="input-icon-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    <input type="password" name="password" placeholder="Password Baru (minimal 8 karakter)" required minlength="8" autocomplete="new-password">
                </div>

                <div class="input-icon-wrap" style="margin-bottom: 18px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    <input type="password" name="password_confirmation" placeholder="Konfirmasi Password Baru" required minlength="8" autocomplete="new-password">
                </div>

                <button type="submit" class="btn-terracotta rect" style="width: 100%; height: 42px; margin-bottom: 10px;">Simpan Password Baru</button>
                <a href="{{ auth()->check() ? route('account') : route('login') }}" class="btn-white" style="width: 100%; height: 42px;">
                    {{ auth()->check() ? 'Kembali ke Akun' : 'Kembali ke Login' }}
                </a>
            </form>
        </div>
    </div>
    <script src="{{ asset('Assets/js/animations.js') }}" defer></script>
</body>

</html>
