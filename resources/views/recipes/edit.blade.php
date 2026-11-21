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

    <form action="{{ route('recipes.update', $recipe) }}" method="POST">
        @csrf
        @method('PUT')

        <div>
            <label for="food_id">Kuliner / Makanan</label><br>
            <select name="food_id" id="food_id" required>
                @foreach ($foods as $food)
                    <option value="{{ $food->id }}" {{ old('food_id', $recipe->food_id) == $food->id ? 'selected' : '' }}>
                        {{ $food->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="title">Judul Resep</label><br>
            <input type="text" name="title" id="title" value="{{ old('title', $recipe->title) }}" required>
        </div>

        <div>
            <label for="prep_time_minutes">Waktu Persiapan (menit)</label><br>
            <input type="number" name="prep_time_minutes" id="prep_time_minutes" value="{{ old('prep_time_minutes', $recipe->prep_time_minutes) }}" min="0" required>
        </div>

        <div>
            <label for="cook_time_minutes">Waktu Memasak (menit)</label><br>
            <input type="number" name="cook_time_minutes" id="cook_time_minutes" value="{{ old('cook_time_minutes', $recipe->cook_time_minutes) }}" min="0" required>
        </div>

        <div>
            <label for="servings">Porsi</label><br>
            <input type="number" name="servings" id="servings" value="{{ old('servings', $recipe->servings) }}" min="1" required>
        </div>

        <div>
            <label for="difficulty">Tingkat Kesulitan</label><br>
            <input type="text" name="difficulty" id="difficulty" value="{{ old('difficulty', $recipe->difficulty) }}" required>
        </div>

        @if (auth()->user()?->isAdmin())
            <div>
                <label for="approval_status">Status Persetujuan</label><br>
                <select name="approval_status" id="approval_status">
                    <option value="pending" {{ old('approval_status', $recipe->approval_status) === 'pending' ? 'selected' : '' }}>pending</option>
                    <option value="approved" {{ old('approval_status', $recipe->approval_status) === 'approved' ? 'selected' : '' }}>approved</option>
                    <option value="rejected" {{ old('approval_status', $recipe->approval_status) === 'rejected' ? 'selected' : '' }}>rejected</option>
                </select>
            </div>
        @endif

        <button type="submit">Update Resep</button>
    </form>
</body>
</html>