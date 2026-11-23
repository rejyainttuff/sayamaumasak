<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kuliner</title>
</head>
<body>
    <h1>Tambah Kuliner</h1>

    <a href="{{ route('foods.index') }}">Kembali ke Daftar Kuliner</a>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('foods.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div>
            <label for="name">Nama Kuliner</label><br>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required>
            @error('name')
                <div>{{ $message }}</div>
            @enderror
        </div>

        <br>

        <div>
            <label for="category_id">Kategori</label><br>
            <select name="category_id" id="category_id" required>
                <option value="">-- Pilih Kategori --</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <div>{{ $message }}</div>
            @enderror
        </div>

        <br>

        <div>
            <label for="origin_area">Asal Daerah</label><br>
            <input type="text" name="origin_area" id="origin_area" value="{{ old('origin_area') }}" required>
            @error('origin_area')
                <div>{{ $message }}</div>
            @enderror
        </div>

        <br>

        <div>
            <label for="short_description">Deskripsi Singkat</label><br>
            <textarea name="short_description" id="short_description" rows="4" required>{{ old('short_description') }}</textarea>
            @error('short_description')
                <div>{{ $message }}</div>
            @enderror
        </div>

        <br>

        <div>
            <label for="thumbnail">Thumbnail (Opsional, maks. 2MB)</label><br>
            <input type="file" name="thumbnail" id="thumbnail" accept=".jpg,.jpeg,.png,.webp">
            @error('thumbnail')
                <div>{{ $message }}</div>
            @enderror
        </div>

        <br>

        <button type="submit">Simpan Kuliner</button>
    </form>
</body>
</html>