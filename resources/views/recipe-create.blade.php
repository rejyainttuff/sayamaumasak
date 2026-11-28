@extends('layouts.app')

@section('title', 'Mari Berbagi Rasa Makanan Bali — Bali Culinary')

@section('content')
    <header class="bali-hero hero-create">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>Mari berbagi rasa<br>makanan <span class="accent">Bali</span></h1>
                <p>Bagikan resep masakan tradisional khas Bali favoritmu agar lebih banyak orang dapat memasak dan melestarikannya.</p>
                <div class="bali-hero-actions">
                    <a href="{{ route('explore') }}" class="pill-tag">&larr; Kembali</a>
                </div>
            </div>
        </div>
    </header>

    <section class="share-wrap">
        <div class="recipe-container">
            @if ($errors->any())
                <div class="alert-banner error">
                    <strong>Mohon lengkapi formulir resep:</strong>
                    <ul style="margin: 6px 0 0; padding-left: 18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="share-layout">
                <aside class="share-left-col">
                    <div class="recipe-guide-visual">
                        <img src="{{ asset('Assets/images/image-for-reciepe.png') }}" alt="Ilustrasi Kuliner Bali" class="recipe-guide-img">
                    </div>

                    <div class="terms-card">
                        <h2>Panduan Pengajuan Resep</h2>
                        <ol class="terms-list">
                            <li>Gunakan nama resep kuliner Bali yang jelas dan mudah dikenali.</li>
                            <li>Sertakan daftar bahan-bahan serta takaran secara lengkap.</li>
                            <li>Tulis langkah memasak secara berurutan agar mudah diikuti pembaca.</li>
                            <li>Gunakan foto hidangan yang relevan, terang, dan tidak buram.</li>
                            <li>Pastikan seluruh informasi resep akurat dan siap ditinjau Admin.</li>
                        </ol>
                    </div>
                </aside>

                <div class="share-form-card">
                    <h2>Formulir Berbagi Resep</h2>

                    <form action="{{ route('recipe.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="name">Nama Resep</label>
                                <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Lawar Nangka Bali" required>
                            </div>

                            <div class="form-group">
                                <label for="description">Deskripsi Singkat</label>
                                <input type="text" id="description" name="description" class="form-control" value="{{ old('description') }}" placeholder="Ceritakan cita rasa atau keunikannya..." required>
                            </div>

                            <div class="form-group">
                                <label for="region">Asal Daerah</label>
                                <select id="region" name="region" class="form-control" required>
                                    <option value="">Pilih Kabupaten / Kota</option>
                                    @foreach ($regions as $region)
                                        <option value="{{ $region }}" @selected(old('region') === $region)>{{ $region }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="servings">Porsi</label>
                                <input type="text" id="servings" name="servings" class="form-control" value="{{ old('servings') }}" placeholder="Contoh: 4 porsi">
                            </div>

                            <div class="form-group">
                                <label for="category">Kategori</label>
                                <select id="category" name="category" class="form-control" required>
                                    <option value="">Pilih Kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="photo">Foto Masakan</label>
                                <div class="photo-upload-row">
                                    <label class="photo-upload-btn" for="photo">
                                        <input type="file" id="photo" name="photo" accept="image/*">
                                        Pilih Foto Masakan
                                    </label>
                                    <div class="photo-upload-preview" id="upload-filename">Belum ada file</div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="cook_time">Waktu Memasak</label>
                                <input type="text" id="cook_time" name="cook_time" class="form-control" value="{{ old('cook_time') }}" placeholder="Contoh: 45 menit" required>
                            </div>

                            <div class="form-group">
                                <label for="video_url">Link Video (Opsional)</label>
                                <input type="text" id="video_url" name="video_url" class="form-control" value="{{ old('video_url') }}" placeholder="https://youtube.com/...">
                            </div>

                            <div class="form-group">
                                <label for="ingredients">Bahan-Bahan (satu per baris)</label>
                                <textarea id="ingredients" name="ingredients" class="form-control" placeholder="250 g kacang panjang&#10;200 g daging ayam cincang&#10;3 sdm bumbu base genep" required>{{ old('ingredients') }}</textarea>
                            </div>

                            <div class="form-group">
                                <label>Langkah-Langkah Memasak</label>
                                <div class="steps-builder" id="steps-builder">
                                    @php
                                        $oldSteps = old('steps', ['', '']);
                                        if (!is_array($oldSteps) || count($oldSteps) === 0) {
                                            $oldSteps = ['', ''];
                                        }
                                    @endphp
                                    @foreach ($oldSteps as $index => $stepValue)
                                        <div class="step-row">
                                            <span class="rank-circle step-number">{{ $index + 1 }}</span>
                                            <input type="text" name="steps[]" class="form-control" value="{{ $stepValue }}" placeholder="Tulis langkah memasak...">
                                            <button type="button" class="step-remove-btn" aria-label="Hapus langkah">&times;</button>
                                        </div>
                                    @endforeach
                                </div>
                                <div style="margin-top: 8px;">
                                    <button type="button" id="add-step-btn" class="pill-tag">+ Tambah Langkah</button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="difficulty">Tingkat Kesulitan</label>
                                <select id="difficulty" name="difficulty" class="form-control" required>
                                    <option value="">Pilih Tingkat Kesulitan</option>
                                    @foreach ($difficulties as $difficulty)
                                        <option value="{{ $difficulty }}" @selected(old('difficulty') === $difficulty)>{{ $difficulty }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label class="checkbox-row">
                            <input type="checkbox" required>
                            <span>Saya menyatakan resep ini asli dan siap ditinjau oleh admin Bali Culinary.</span>
                        </label>

                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="submit" name="submission_action" value="draft" class="btn-white">Simpan sebagai Draft</button>
                            <button type="submit" name="submission_action" value="submit" class="btn-terracotta rect">Kirim Resep</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
                        : 'Belum ada file';
                });
            }
        })();
    </script>
@endpush
