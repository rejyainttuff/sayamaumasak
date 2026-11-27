@extends('layouts.app')

@section('title', 'Kirim Cerita Kuliner Lengkap — Bali Culinary')

@section('content')
    <header class="bali-hero hero-create">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <span class="editorial-kicker">Khusus Contributor Level 3+</span>
                <h1>Kirim Cerita <span class="accent">Kuliner</span></h1>
                <p>Bagikan sejarah, tradisi, galeri foto, dan video dokumenter kuliner tradisional Bali.</p>
                <div class="bali-hero-actions">
                    <a href="{{ route('stories') }}" class="pill-tag">&larr; Kembali ke Cerita</a>
                </div>
            </div>
        </div>
    </header>

    <section class="home-section">
        <div class="recipe-container" style="max-width: 780px;">
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
                <form action="{{ route('stories.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label for="title">Judul Cerita</label>
                        <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="food_id">Terkait Kuliner (Opsional)</label>
                            <select name="food_id" id="food_id" class="form-control">
                                <option value="">-- Pilih Kuliner --</option>
                                @foreach ($foods as $food)
                                    <option value="{{ $food->id }}" @selected((string) old('food_id') === (string) $food->id)>
                                        {{ $food->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="video_url">URL Video YouTube / Dokumenter (Opsional)</label>
                            <input type="url" name="video_url" id="video_url" class="form-control" value="{{ old('video_url') }}" placeholder="https://youtube.com/watch?v=...">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="thumbnail">Foto Utama / Sampul (Opsional)</label>
                            <input type="file" name="thumbnail" id="thumbnail" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <div class="form-group">
                            <label for="gallery_images">Galeri Foto Tambahan (Maks. 6 foto)</label>
                            <input type="file" name="gallery_images[]" id="gallery_images" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="content">Isi Cerita Lengkap (Minimal 50 karakter)</label>
                        <textarea name="content" id="content" rows="8" class="form-control" required>{{ old('content') }}</textarea>
                    </div>

                    <button type="submit" class="btn-terracotta rect">Kirim Cerita Kuliner</button>
                </form>
            </div>
        </div>
    </section>
@endsection
