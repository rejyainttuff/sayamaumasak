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
                    <a href="{{ route('submissions.index') }}" class="pill-tag">&larr; Kembali ke My Submissions</a>
                </div>
            </div>
        </div>
    </header>

    <section class="home-section">
        <div class="recipe-container" style="max-width: 780px;">
            @if ($errors->any())
                <div class="alert-banner error">
                    <strong>Mohon periksa kembali formulir:</strong>
                    <ul style="margin: 6px 0 0; padding-left: 18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="share-form-card">
                <form action="{{ route('stories.store') }}" method="POST" enctype="multipart/form-data" id="story-create-form" data-loading-form>
                    @csrf

                    <div class="form-group">
                        <label for="title">Judul Cerita</label>
                        <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" placeholder="Contoh: Tradisi Megibung di Karangasem" required>
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

                    <div class="form-group">
                        <label for="thumbnail">Foto Utama / Sampul (Opsional, JPG/PNG/WEBP maks. 2MB)</label>
                        <input type="file" name="thumbnail" id="thumbnail" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>

                    {{-- Interactive Multiple Photo Upload (Max 6 Photos, Live Preview & Remove) --}}
                    <div class="form-group">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 8px;">
                            <label for="gallery_images" style="margin: 0;">Foto Tambahan Galeri (Maks. 6 foto, masing-masing maks. 2MB)</label>
                            <span class="pill-tag" id="gallery-count-badge">0 / 6 foto dipilih</span>
                        </div>

                        <div class="multi-upload-box" id="multi-upload-box">
                            <input type="file" name="gallery_images[]" id="gallery_images" accept="image/jpeg,image/png,image/webp" multiple style="display: none;">
                            <button type="button" id="add-gallery-photos-btn" class="btn-white" style="height: 38px;">
                                + Pilih Foto (Bisa Beberapa Sekaligus)
                            </button>
                            <p style="font-size: 12px; color: var(--muted); margin: 8px 0 0;">
                                Format: JPG, PNG, WEBP &middot; Maksimal 2MB per foto &middot; Anda dapat memilih beberapa foto sekaligus atau menambah bertahap hingga 6 foto.
                            </p>
                            <div id="gallery-error-msg" style="display: none; margin-top: 8px; font-size: 12.5px; color: #b42318; font-weight: 500;"></div>
                            <div class="multi-upload-preview-grid" id="gallery-preview-grid"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="content">Isi Cerita Lengkap (Minimal 50 karakter)</label>
                        <textarea name="content" id="content" rows="8" class="form-control" placeholder="Tulis cerita, sejarah, dan pengalaman kuliner Bali secara lengkap..." required>{{ old('content') }}</textarea>
                    </div>

                    <button type="submit" class="btn-terracotta rect">Kirim Cerita Kuliner</button>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const MAX_FILES = 6;
            const MAX_SIZE_BYTES = 2 * 1024 * 1024;
            const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

            const fileInput = document.getElementById('gallery_images');
            const addBtn = document.getElementById('add-gallery-photos-btn');
            const previewGrid = document.getElementById('gallery-preview-grid');
            const countBadge = document.getElementById('gallery-count-badge');
            const errorBox = document.getElementById('gallery-error-msg');

            if (!fileInput || !addBtn || !previewGrid || !countBadge) {
                return;
            }

            let selectedFiles = [];

            function showError(msg) {
                if (!errorBox) {
                    return;
                }
                if (msg) {
                    errorBox.textContent = msg;
                    errorBox.style.display = 'block';
                } else {
                    errorBox.textContent = '';
                    errorBox.style.display = 'none';
                }
            }

            function syncFileInput() {
                const dt = new DataTransfer();
                selectedFiles.forEach(function (file) {
                    dt.items.add(file);
                });
                fileInput.files = dt.files;

                countBadge.textContent = selectedFiles.length + ' / ' + MAX_FILES + ' foto dipilih';
                addBtn.disabled = selectedFiles.length >= MAX_FILES;
                addBtn.textContent = selectedFiles.length >= MAX_FILES
                    ? 'Batas Maksimal 6 Foto Tercapai'
                    : '+ Pilih Foto (Bisa Beberapa Sekaligus)';
            }

            function renderPreviews() {
                previewGrid.innerHTML = '';
                selectedFiles.forEach(function (file, index) {
                    const card = document.createElement('div');
                    card.className = 'multi-upload-thumb';

                    const img = document.createElement('img');
                    img.alt = file.name;
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'multi-upload-remove';
                    removeBtn.setAttribute('aria-label', 'Hapus foto ' + file.name);
                    removeBtn.innerHTML = '&times;';
                    removeBtn.addEventListener('click', function () {
                        selectedFiles.splice(index, 1);
                        showError('');
                        syncFileInput();
                        renderPreviews();
                    });

                    const caption = document.createElement('span');
                    caption.className = 'multi-upload-name';
                    caption.textContent = file.name;

                    card.appendChild(img);
                    card.appendChild(removeBtn);
                    card.appendChild(caption);
                    previewGrid.appendChild(card);
                });
            }

            addBtn.addEventListener('click', function () {
                if (selectedFiles.length >= MAX_FILES) {
                    showError('Maksimal 6 foto tambahan sudah tercapai.');
                    return;
                }
                // Create a temporary picker so selecting multiple batches appends cleanly
                const picker = document.createElement('input');
                picker.type = 'file';
                picker.accept = 'image/jpeg,image/png,image/webp';
                picker.multiple = true;
                picker.addEventListener('change', function () {
                    const incoming = Array.from(picker.files || []);
                    let warning = '';

                    for (let i = 0; i < incoming.length; i++) {
                        const file = incoming[i];
                        if (!ALLOWED_TYPES.includes(file.type)) {
                            warning = 'Beberapa file diabaikan karena bukan format JPG, PNG, atau WEBP.';
                            continue;
                        }
                        if (file.size > MAX_SIZE_BYTES) {
                            warning = 'Beberapa file diabaikan karena ukurannya melebihi 2MB.';
                            continue;
                        }
                        if (selectedFiles.length >= MAX_FILES) {
                            warning = 'Maksimal 6 foto tambahan. Foto selebihnya tidak ditambahkan.';
                            break;
                        }
                        selectedFiles.push(file);
                    }

                    showError(warning);
                    syncFileInput();
                    renderPreviews();
                });
                picker.click();
            });

            const storyForm = document.getElementById('story-create-form');
            if (storyForm) {
                storyForm.addEventListener('submit', function () {
                    syncFileInput();
                });
            }

            syncFileInput();
        })();
    </script>
@endpush
