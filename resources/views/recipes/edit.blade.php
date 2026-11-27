@extends('layouts.app')

@section('title', 'Edit Resep — '.$recipe->title.' | Bali Culinary')

@section('content')
    <header class="bali-hero hero-create">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>Edit Resep<br><span class="accent">{{ $recipe->title }}</span></h1>
                <p>Perbarui informasi resep, bahan, langkah memasak, atau foto makanan tanpa kehilangan data yang sudah tersimpan.</p>
                <div class="bali-hero-actions">
                    <a href="{{ auth()->user()?->isAdmin() ? route('admin.recipes.approvals') : route('recipes.index') }}" class="pill-tag">&larr; Kembali</a>
                </div>
            </div>
        </div>
    </header>

    <section class="share-wrap">
        <div class="recipe-container">
            @if ($errors->any())
                <div class="alert-banner error">
                    <strong>Mohon periksa kembali formulir resep:</strong>
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
                        <h2>Panduan Penyuntingan Resep</h2>
                        <ol class="terms-list">
                            <li>Pastikan judul dan kuliner terkait sesuai dengan nama hidangan Bali.</li>
                            <li>Periksa kelengkapan bahan dan takaran agar mudah diikuti pembaca.</li>
                            <li>Pastikan urutan langkah memasak jelas dan runtut.</li>
                            <li>Unggah foto baru hanya jika ingin mengganti gambar sebelumnya.</li>
                        </ol>
                    </div>
                </aside>

                <div class="share-form-card">
                    <h2>Formulir Edit Resep</h2>

                    <form action="{{ route('recipes.update', $recipe) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="food_id">Kuliner / Makanan Terkait</label>
                                <select name="food_id" id="food_id" class="form-control" required>
                                    @foreach ($foods as $food)
                                        <option value="{{ $food->id }}" @selected(old('food_id', $recipe->food_id) == $food->id)>
                                            {{ $food->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="title">Judul Resep</label>
                                <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $recipe->title) }}" required>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="prep_time_minutes">Waktu Persiapan (menit)</label>
                                <input type="number" name="prep_time_minutes" id="prep_time_minutes" class="form-control" value="{{ old('prep_time_minutes', $recipe->prep_time_minutes) }}" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="cook_time_minutes">Waktu Memasak (menit)</label>
                                <input type="number" name="cook_time_minutes" id="cook_time_minutes" class="form-control" value="{{ old('cook_time_minutes', $recipe->cook_time_minutes) }}" min="0" required>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="servings">Porsi</label>
                                <input type="number" name="servings" id="servings" class="form-control" value="{{ old('servings', $recipe->servings) }}" min="1" required>
                            </div>

                            <div class="form-group">
                                <label for="difficulty">Tingkat Kesulitan</label>
                                <select name="difficulty" id="difficulty" class="form-control" required>
                                    @php $currentDiff = strtolower((string) old('difficulty', $recipe->difficulty)); @endphp
                                    <option value="mudah" @selected(in_array($currentDiff, ['mudah', 'easy'], true))>Mudah</option>
                                    <option value="sedang" @selected(in_array($currentDiff, ['sedang', 'medium', ''], true))>Sedang</option>
                                    <option value="sulit" @selected(in_array($currentDiff, ['sulit', 'hard'], true))>Sulit</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="video_url">URL Video Tutorial (Opsional)</label>
                                <input type="text" name="video_url" id="video_url" class="form-control" value="{{ old('video_url', $recipe->video_url) }}" placeholder="https://youtube.com/...">
                            </div>

                            <div class="form-group">
                                <label for="food_thumbnail">Perbarui Foto Makanan (Opsional)</label>
                                <input type="file" name="food_thumbnail" id="food_thumbnail" class="form-control" accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>

                        @if (auth()->user()?->isAdmin())
                            <div class="form-group">
                                <label for="approval_status">Status Persetujuan</label>
                                <select name="approval_status" id="approval_status" class="form-control">
                                    <option value="pending" @selected(old('approval_status', $recipe->approval_status) === 'pending')>Pending</option>
                                    <option value="approved" @selected(old('approval_status', $recipe->approval_status) === 'approved')>Approved</option>
                                    <option value="rejected" @selected(old('approval_status', $recipe->approval_status) === 'rejected')>Rejected</option>
                                </select>
                            </div>
                        @endif

                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label style="margin: 0;">Bahan-Bahan</label>
                                <button type="button" id="add-edit-ingredient" class="pill-tag">+ Tambah Bahan</button>
                            </div>
                            <div id="ingredients-container" style="display: flex; flex-direction: column; gap: 10px;">
                                @php
                                    $existingIngredients = $recipe->recipeIngredients->map(fn ($ri) => [
                                        'ingredient_id' => $ri->ingredient_id,
                                        'ingredient_name' => '',
                                        'amount' => $ri->amount,
                                        'unit' => $ri->unit,
                                        'notes' => $ri->notes,
                                    ])->toArray();

                                    $oldIngredients = old('ingredients', count($existingIngredients) ? $existingIngredients : [
                                        ['ingredient_id' => '', 'ingredient_name' => '', 'amount' => '', 'unit' => '', 'notes' => '']
                                    ]);
                                @endphp

                                @foreach ($oldIngredients as $i => $item)
                                    <div class="ingredient-row" style="border: 1px solid var(--border); border-radius: 10px; padding: 12px; background: var(--bg-page);">
                                        <div class="form-grid-2">
                                            <select name="ingredients[{{ $i }}][ingredient_id]" class="form-control">
                                                <option value="">-- Pilih Bahan --</option>
                                                @foreach ($ingredients as $ingredient)
                                                    <option value="{{ $ingredient->id }}" @selected(($item['ingredient_id'] ?? '') == $ingredient->id)>
                                                        {{ $ingredient->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="ingredients[{{ $i }}][ingredient_name]" class="form-control" value="{{ $item['ingredient_name'] ?? '' }}" placeholder="Atau tulis bahan baru">
                                        </div>
                                        <div class="form-grid-2" style="margin-top: 8px;">
                                            <input type="number" step="0.01" name="ingredients[{{ $i }}][amount]" class="form-control" value="{{ $item['amount'] ?? '' }}" placeholder="Jumlah">
                                            <input type="text" name="ingredients[{{ $i }}][unit]" class="form-control" value="{{ $item['unit'] ?? '' }}" placeholder="Satuan (gram, sdm, ikat)">
                                        </div>
                                        <input type="text" name="ingredients[{{ $i }}][notes]" class="form-control" style="margin-top: 8px;" value="{{ $item['notes'] ?? '' }}" placeholder="Catatan (opsional)">
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label style="margin: 0;">Langkah Memasak</label>
                                <button type="button" id="add-edit-step" class="pill-tag">+ Tambah Langkah</button>
                            </div>
                            <div id="steps-container" style="display: flex; flex-direction: column; gap: 10px;">
                                @php
                                    $existingSteps = $recipe->recipeSteps->sortBy('step_number')->values()->map(fn ($step) => [
                                        'id' => $step->id,
                                        'step_number' => $step->step_number,
                                        'instruction' => $step->instruction,
                                    ])->toArray();

                                    $oldSteps = old('steps', count($existingSteps) ? $existingSteps : [
                                        ['id' => '', 'step_number' => 1, 'instruction' => '']
                                    ]);
                                @endphp

                                @foreach ($oldSteps as $i => $step)
                                    <div class="step-row" style="border: 1px solid var(--border); border-radius: 10px; padding: 12px; background: var(--bg-page);">
                                        <input type="hidden" name="steps[{{ $i }}][id]" value="{{ $step['id'] ?? '' }}">
                                        <input type="hidden" name="steps[{{ $i }}][step_number]" value="{{ $step['step_number'] ?? ($i + 1) }}">
                                        <label style="font-size: 12px; color: var(--muted);">Langkah {{ $i + 1 }}</label>
                                        <textarea name="steps[{{ $i }}][instruction]" rows="2" class="form-control" required>{{ $step['instruction'] ?? '' }}</textarea>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="submit" class="btn-terracotta rect">Simpan Perubahan Resep</button>
                            <a href="{{ auth()->user()?->isAdmin() ? route('admin.recipes.approvals') : route('recipes.index') }}" class="btn-white">Batal</a>
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
            const ingBtn = document.getElementById('add-edit-ingredient');
            const ingContainer = document.getElementById('ingredients-container');
            if (ingBtn && ingContainer) {
                ingBtn.addEventListener('click', function () {
                    const idx = ingContainer.querySelectorAll('.ingredient-row').length;
                    const div = document.createElement('div');
                    div.className = 'ingredient-row';
                    div.style.cssText = 'border: 1px solid var(--border); border-radius: 10px; padding: 12px; background: var(--bg-page);';
                    div.innerHTML =
                        '<div class="form-grid-2">' +
                        '<input type="text" name="ingredients[' + idx + '][ingredient_name]" class="form-control" placeholder="Nama bahan">' +
                        '<input type="text" name="ingredients[' + idx + '][unit]" class="form-control" placeholder="Satuan (gram, sdm)">' +
                        '</div>' +
                        '<div class="form-grid-2" style="margin-top: 8px;">' +
                        '<input type="number" step="0.01" name="ingredients[' + idx + '][amount]" class="form-control" placeholder="Jumlah">' +
                        '<input type="text" name="ingredients[' + idx + '][notes]" class="form-control" placeholder="Catatan (opsional)">' +
                        '</div>';
                    ingContainer.appendChild(div);
                });
            }

            const stepBtn = document.getElementById('add-edit-step');
            const stepContainer = document.getElementById('steps-container');
            if (stepBtn && stepContainer) {
                stepBtn.addEventListener('click', function () {
                    const idx = stepContainer.querySelectorAll('.step-row').length;
                    const div = document.createElement('div');
                    div.className = 'step-row';
                    div.style.cssText = 'border: 1px solid var(--border); border-radius: 10px; padding: 12px; background: var(--bg-page);';
                    div.innerHTML =
                        '<input type="hidden" name="steps[' + idx + '][step_number]" value="' + (idx + 1) + '">' +
                        '<label style="font-size: 12px; color: var(--muted);">Langkah ' + (idx + 1) + '</label>' +
                        '<textarea name="steps[' + idx + '][instruction]" rows="2" class="form-control" required></textarea>';
                    stepContainer.appendChild(div);
                });
            }
        })();
    </script>
@endpush
