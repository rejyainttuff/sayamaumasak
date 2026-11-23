<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resep</title>
</head>
<body>
    <h1>Edit Resep</h1>
    <a href="{{ route('recipes.index') }}">Kembali ke Daftar Resep</a>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('recipes.update', $recipe) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div>
            <label for="food_id">Kuliner / Makanan:</label><br>
            <select name="food_id" id="food_id" required>
                @foreach ($foods as $food)
                    <option value="{{ $food->id }}" {{ old('food_id', $recipe->food_id) == $food->id ? 'selected' : '' }}>
                        {{ $food->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="title">Judul Resep:</label><br>
            <input type="text" name="title" id="title" value="{{ old('title', $recipe->title) }}" required>
        </div>

        <div>
            <label for="prep_time_minutes">Waktu Persiapan (menit):</label><br>
            <input type="number" name="prep_time_minutes" id="prep_time_minutes" value="{{ old('prep_time_minutes', $recipe->prep_time_minutes) }}" min="0" required>
        </div>

        <div>
            <label for="cook_time_minutes">Waktu Memasak (menit):</label><br>
            <input type="number" name="cook_time_minutes" id="cook_time_minutes" value="{{ old('cook_time_minutes', $recipe->cook_time_minutes) }}" min="0" required>
        </div>

        <div>
            <label for="servings">Porsi:</label><br>
            <input type="number" name="servings" id="servings" value="{{ old('servings', $recipe->servings) }}" min="1" required>
        </div>

        <div>
            <label for="difficulty">Tingkat Kesulitan:</label><br>
            <input type="text" name="difficulty" id="difficulty" value="{{ old('difficulty', $recipe->difficulty) }}" required>
        </div>

        @if (auth()->user()?->isAdmin())
            <div>
                <label for="approval_status">Status Persetujuan:</label><br>
                <select name="approval_status" id="approval_status">
                    <option value="pending" {{ old('approval_status', $recipe->approval_status) === 'pending' ? 'selected' : '' }}>pending</option>
                    <option value="approved" {{ old('approval_status', $recipe->approval_status) === 'approved' ? 'selected' : '' }}>approved</option>
                    <option value="rejected" {{ old('approval_status', $recipe->approval_status) === 'rejected' ? 'selected' : '' }}>rejected</option>
                </select>
            </div>
        @endif

        <hr>

        <h2>Bahan-Bahan</h2>
        <div id="ingredients-container">
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
                <div class="ingredient-row" style="border: 1px solid #ccc; padding: 8px; margin-bottom: 8px;">
                    <label>Pilih Bahan:</label><br>
                    <select name="ingredients[{{ $i }}][ingredient_id]">
                        <option value="">-- Pilih Bahan Tersedia --</option>
                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}" {{ ($item['ingredient_id'] ?? '') == $ingredient->id ? 'selected' : '' }}>
                                {{ $ingredient->name }}
                            </option>
                        @endforeach
                    </select><br>

                    <label>Atau Bahan Baru:</label><br>
                    <input type="text" name="ingredients[{{ $i }}][ingredient_name]" value="{{ $item['ingredient_name'] ?? '' }}" placeholder="Contoh: Bawang merah"><br>

                    <label>Jumlah:</label><br>
                    <input type="number" step="any" name="ingredients[{{ $i }}][amount]" value="{{ $item['amount'] ?? '' }}" placeholder="Contoh: 5"><br>

                    <label>Satuan:</label><br>
                    <input type="text" name="ingredients[{{ $i }}][unit]" value="{{ $item['unit'] ?? '' }}" placeholder="Contoh: siung, gram, sdt"><br>

                    <label>Catatan:</label><br>
                    <input type="text" name="ingredients[{{ $i }}][notes]" value="{{ $item['notes'] ?? '' }}" placeholder="Contoh: iris tipis"><br>

                    <button type="button" class="remove-row">Hapus Bahan</button>
                </div>
            @endforeach
        </div>
        <button type="button" id="add-ingredient">+ Tambah Bahan</button>

        <hr>

        <h2>Langkah-Langkah Memasak</h2>
        <div id="steps-container">
            @php
                $existingSteps = $recipe->recipeSteps->map(fn ($st) => [
                    'id' => $st->id,
                    'step_number' => $st->step_number,
                    'instruction' => $st->instruction,
                    'existing_image' => $st->image,
                ])->toArray();

                $oldSteps = old('steps', count($existingSteps) ? $existingSteps : [
                    ['id' => '', 'step_number' => 1, 'instruction' => '', 'existing_image' => null]
                ]);
            @endphp

            @foreach ($oldSteps as $s => $step)
                <div class="step-row" style="border: 1px solid #ccc; padding: 8px; margin-bottom: 8px;">
                    @if (!empty($step['id']))
                        <input type="hidden" name="steps[{{ $s }}][id]" value="{{ $step['id'] }}">
                    @endif

                    <label>Urutan Langkah:</label><br>
                    <input type="number" name="steps[{{ $s }}][step_number]" value="{{ $step['step_number'] ?? ($s + 1) }}" min="1"><br>

                    <label>Instruksi:</label><br>
                    <textarea name="steps[{{ $s }}][instruction]" rows="3" cols="40" required>{{ $step['instruction'] ?? '' }}</textarea><br>

                    @if (!empty($step['existing_image']))
                        <div>
                            <small>Gambar saat ini:</small><br>
                            <img src="{{ asset('storage/' . $step['existing_image']) }}" alt="Langkah {{ $step['step_number'] ?? ($s + 1) }}" style="max-width: 150px; height: auto;">
                        </div>
                    @endif

                    <label>Ganti / Upload Gambar Langkah (Opsional):</label><br>
                    <input type="file" name="steps[{{ $s }}][image]" accept="image/*"><br>

                    <button type="button" class="remove-row">Hapus Langkah</button>
                </div>
            @endforeach
        </div>
        <button type="button" id="add-step">+ Tambah Langkah</button>

        <hr>

        <button type="submit">Update Resep</button>
    </form>

    <script>
        const ingredientOptionsHtml = `
            <option value="">-- Pilih Bahan Tersedia --</option>
            @foreach ($ingredients as $ingredient)
                <option value="{{ $ingredient->id }}">{{ $ingredient->name }}</option>
            @endforeach
        `;

        let ingredientIndex = {{ count($oldIngredients) }};
        let stepIndex = {{ count($oldSteps) }};

        document.getElementById('add-ingredient').addEventListener('click', function () {
            const container = document.getElementById('ingredients-container');
            const row = document.createElement('div');
            row.className = 'ingredient-row';
            row.style = 'border: 1px solid #ccc; padding: 8px; margin-bottom: 8px;';
            row.innerHTML = `
                <label>Pilih Bahan:</label><br>
                <select name="ingredients[${ingredientIndex}][ingredient_id]">${ingredientOptionsHtml}</select><br>

                <label>Atau Bahan Baru:</label><br>
                <input type="text" name="ingredients[${ingredientIndex}][ingredient_name]" placeholder="Contoh: Bawang merah"><br>

                <label>Jumlah:</label><br>
                <input type="number" step="any" name="ingredients[${ingredientIndex}][amount]" placeholder="Contoh: 5"><br>

                <label>Satuan:</label><br>
                <input type="text" name="ingredients[${ingredientIndex}][unit]" placeholder="Contoh: siung, gram, sdt"><br>

                <label>Catatan:</label><br>
                <input type="text" name="ingredients[${ingredientIndex}][notes]" placeholder="Contoh: iris tipis"><br>

                <button type="button" class="remove-row">Hapus Bahan</button>
            `;
            container.appendChild(row);
            ingredientIndex++;
        });

        document.getElementById('add-step').addEventListener('click', function () {
            const container = document.getElementById('steps-container');
            const nextNumber = container.querySelectorAll('.step-row').length + 1;
            const row = document.createElement('div');
            row.className = 'step-row';
            row.style = 'border: 1px solid #ccc; padding: 8px; margin-bottom: 8px;';
            row.innerHTML = `
                <label>Urutan Langkah:</label><br>
                <input type="number" name="steps[${stepIndex}][step_number]" value="${nextNumber}" min="1"><br>

                <label>Instruksi:</label><br>
                <textarea name="steps[${stepIndex}][instruction]" rows="3" cols="40" placeholder="Tuliskan langkah memasak..." required></textarea><br>

                <label>Gambar Langkah (Opsional):</label><br>
                <input type="file" name="steps[${stepIndex}][image]" accept="image/*"><br>

                <button type="button" class="remove-row">Hapus Langkah</button>
            `;
            container.appendChild(row);
            stepIndex++;
        });

        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('remove-row')) {
                const row = e.target.parentElement;
                const parent = row.parentElement;
                if (parent.children.length > 1) {
                    row.remove();
                } else {
                    alert('Minimal harus ada 1 baris.');
                }
            }
        });
    </script>
</body>
</html>