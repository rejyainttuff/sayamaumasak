<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Resep</title>
</head>
<body>
    <h1>Tambah Resep</h1>

    <a href="{{ route('recipes.index') }}">Kembali ke Daftar Resep</a>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('recipes.store') }}" method="POST">
        @csrf

        <div>
            <label for="food_id">Kuliner / Makanan</label><br>
            <select name="food_id" id="food_id" required>
                <option value="">-- Pilih Kuliner --</option>
                @foreach ($foods as $food)
                    <option value="{{ $food->id }}" {{ old('food_id') == $food->id ? 'selected' : '' }}>
                        {{ $food->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="title">Judul Resep</label><br>
            <input type="text" name="title" id="title" value="{{ old('title') }}" required>
        </div>

        <div>
            <label for="prep_time_minutes">Waktu Persiapan (menit)</label><br>
            <input type="number" name="prep_time_minutes" id="prep_time_minutes" value="{{ old('prep_time_minutes', 15) }}" min="0" required>
        </div>

        <div>
            <label for="cook_time_minutes">Waktu Memasak (menit)</label><br>
            <input type="number" name="cook_time_minutes" id="cook_time_minutes" value="{{ old('cook_time_minutes', 30) }}" min="0" required>
        </div>

        <div>
            <label for="servings">Porsi</label><br>
            <input type="number" name="servings" id="servings" value="{{ old('servings', 4) }}" min="1" required>
        </div>

        <div>
            <label for="difficulty">Tingkat Kesulitan</label><br>
            <input type="text" name="difficulty" id="difficulty" value="{{ old('difficulty', 'Mudah') }}" required>
        </div>

        <button type="submit">Simpan Resep</button>
    </form>
</body>
</html>