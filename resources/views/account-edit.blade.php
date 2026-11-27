@extends('layouts.app')

@section('title', 'Edit Profile — Bali Culinary')

@section('content')
    <header class="bali-hero hero-dashboard">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>Edit <span class="accent">Profil</span></h1>
                <p>Perbarui nama tampilan, bio singkat, dan foto profil akun Bali Culinary kamu.</p>
                <div class="bali-hero-actions">
                    <a href="{{ route('account') }}" class="pill-tag">&larr; Kembali ke Akun</a>
                </div>
            </div>
        </div>
    </header>

    <section class="home-section">
        <div class="recipe-container" style="max-width: 720px;">
            @if ($errors->any())
                <div class="alert-banner error">
                    <ul style="margin: 0; padding-left: 18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="share-form-card">
                <form action="{{ route('account.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="form-group" style="display: flex; align-items: center; gap: 18px; flex-wrap: wrap;">
                        <div class="account-avatar" style="overflow: hidden; flex-shrink: 0;">
                            @if (! empty($user->avatar))
                                <img src="{{ asset(ltrim($user->avatar, '/')) }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            @endif
                        </div>

                        <div style="flex: 1; min-width: 220px;">
                            <label for="avatar">Foto Profil (JPG, PNG, WEBP maks. 2MB)</label>
                            <input type="file" id="avatar" name="avatar" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="name">Nama Lengkap</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio Singkat</label>
                        <textarea id="bio" name="bio" rows="4" class="form-control" placeholder="Ceritakan sedikit tentang dirimu dan kuliner Bali favoritmu...">{{ old('bio', $user->bio) }}</textarea>
                    </div>

                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="submit" class="btn-terracotta rect">Simpan Perubahan</button>
                        <a href="{{ route('account') }}" class="btn-white">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
