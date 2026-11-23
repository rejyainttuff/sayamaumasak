@extends('layouts.app')

@section('title', 'Mari Berbagi Resep Bali — SayaMauMasak')

@section('content')
    <header class="recipe-hero">
        <div class="recipe-hero-inner">
            <div class="recipe-hero-content">
                <a href="{{ route('recipe') }}" class="recipe-back-pill">&larr; Kembali</a>
                <h1>Mari berbagi<br>Resep <span class="accent">Bali</span></h1>
                <p>Bagikan resep kuliner tradisional Bali favoritmu agar lebih banyak orang dapat memasak dan melestarikannya.</p>
            </div>
        </div>
    </header>

    <section class="recipe-container">
        @if ($errors->any())
            <div class="alert-banner error">
                <strong>Mohon lengkapi formulir:</strong>
                <ul style="margin: 8px 0 0; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('recipe.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-top-actions">
                <a href="{{ route('recipe') }}" class="btn-outline">Batal</a>
                <button type="submit" class="btn-sage">Simpan Resep</button>
            </div>

            <div class="recipe-form-card">
                <h2>Formulir Resep</h2>
                <p class="recipe-form-subtitle">Lengkapi detail hidangan, bahan-bahan, dan langkah memasak dengan jelas.</p>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="name">Nama Resep / Hidangan</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control"
                            value="{{ old('name') }}"
                            placeholder="Contoh: Lawar Nangka Muda Bali"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="region">Asal Daerah</label>
                        <select id="region" name="region" class="form-control" required>
                            <option value="">Pilih Kabupaten / Kota di Bali</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region }}" @selected(old('region') === $region)>{{ $region }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="difficulty">Tingkat Kesulitan</label>
                        <select id="difficulty" name="difficulty" class="form-control" required>
                            <option value="">Pilih tingkat kesulitan</option>
                            @foreach ($difficulties as $difficulty)
                                <option value="{{ $difficulty }}" @selected(old('difficulty') === $difficulty)>{{ $difficulty }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="">Pilih kategori hidangan</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="photo">Foto Resep</label>
                        <label class="upload-dropzone" for="photo">
                            <input type="file" id="photo" name="photo" accept="image/*">
                            <strong id="upload-filename">Pilih atau seret gambar ke sini</strong>
                            <span>Format JPG, PNG, atau WEBP (opsional)</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="description">Deskripsi Singkat / Sejarah</label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            placeholder="Ceritakan singkat keunikan rasa atau asal-usul hidangan ini..."
                            required
                        >{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label for="cook_time">Waktu Memasak</label>
                    <input
                        type="text"
                        id="cook_time"
                        name="cook_time"
                        class="form-control"
                        value="{{ old('cook_time') }}"
                        placeholder="Contoh: 45 menit"
                        required
                    >
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="ingredients">Bahan-bahan (satu bahan per baris)</label>
                        <textarea
                            id="ingredients"
                            name="ingredients"
                            class="form-control"
                            style="min-height: 165px;"
                            placeholder="250 gram kacang panjang&#10;200 gram daging ayam cincang&#10;3 sdm bumbu base genep"
                            required
                        >{{ old('ingredients') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Langkah-langkah Memasak</label>
                        <div class="steps-builder" id="steps-builder">
                            @php
                                $oldSteps = old('steps', ['', '', '']);
                                if (!is_array($oldSteps) || count($oldSteps) === 0) {
                                    $oldSteps = ['', '', ''];
                                }
                            @endphp
                            @foreach ($oldSteps as $index => $stepValue)
                                <div class="step-row">
                                    <span class="rank-circle step-number">{{ $index + 1 }}</span>
                                    <input
                                        type="text"
                                        name="steps[]"
                                        class="form-control"
                                        value="{{ $stepValue }}"
                                        placeholder="Tulis langkah memasak..."
                                    >
                                    <button type="button" class="step-remove-btn" aria-label="Hapus langkah">&times;</button>
                                </div>
                            @endforeach
                        </div>

                        <div style="margin-top: 12px;">
                            <button type="button" id="add-step-btn" class="btn-terracotta rect" style="height: 38px; font-size: 13px;">
                                + Tambah Langkah
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const builder = document.getElementById('steps-builder');
            const addBtn = document.getElementById('add-step-btn');
            const fileInput = document.getElementById('photo');
            const fileLabel = document.getElementById('upload-filename');

            function renumberSteps() {
                if (!builder) {
                    return;
                }
                builder.querySelectorAll('.step-number').forEach(function (el, idx) {
                    el.textContent = idx + 1;
                });
            }

            if (addBtn && builder) {
                addBtn.addEventListener('click', function () {
                    const row = document.createElement('div');
                    row.className = 'step-row';
                    row.innerHTML =
                        '<span class="rank-circle step-number"></span>' +
                        '<input type="text" name="steps[]" class="form-control" placeholder="Tulis langkah memasak...">' +
                        '<button type="button" class="step-remove-btn" aria-label="Hapus langkah">&times;</button>';
                    builder.appendChild(row);
                    renumberSteps();
                });

                builder.addEventListener('click', function (event) {
                    const removeBtn = event.target.closest('.step-remove-btn');
                    if (!removeBtn) {
                        return;
                    }
                    const rows = builder.querySelectorAll('.step-row');
                    if (rows.length <= 1) {
                        rows[0].querySelector('input').value = '';
                        return;
                    }
                    removeBtn.closest('.step-row').remove();
                    renumberSteps();
                });
            }

            if (fileInput && fileLabel) {
                fileInput.addEventListener('change', function () {
                    fileLabel.textContent = fileInput.files && fileInput.files[0]
                        ? fileInput.files[0].name
                        : 'Pilih atau seret gambar ke sini';
                });
            }
        })();
    </script>
@endpush
