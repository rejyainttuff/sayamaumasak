@extends('layouts.app')

@section('title', 'Kirim Cerita Kuliner — SayaMauMasak')

@section('content')
<section class="recipe-container" style="margin-top: 24px; margin-bottom: 40px;">
    <h1>Kirim Cerita Kuliner</h1>
    <p><a href="{{ route('stories') }}">&larr; Kembali ke Cerita Kuliner</a></p>

    @if ($errors->any())
        <div class="alert-banner error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('stories.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div style="margin-bottom: 14px;">
            <label for="title">Judul Cerita</label><br>
            <input type="text" name="title" id="title" value="{{ old('title') }}" required>
        </div>

        <div style="margin-bottom: 14px;">
            <label for="food_id">Terkait Kuliner (Opsional)</label><br>
            <select name="food_id" id="food_id">
                <option value="">-- Pilih Kuliner --</option>
                @foreach ($foods as $food)
                    <option value="{{ $food->id }}" {{ (string) old('food_id') === (string) $food->id ? 'selected' : '' }}>
                        {{ $food->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom: 14px;">
            <label for="thumbnail">Foto / Thumbnail (Opsional)</label><br>
            <input type="file" name="thumbnail" id="thumbnail" accept="image/*">
        </div>

        <div style="margin-bottom: 16px;">
            <label for="content">Isi Cerita</label><br>
            <textarea name="content" id="content" rows="6" required>{{ old('content') }}</textarea>
        </div>

        <button type="submit" class="btn-terracotta rect">Kirim Cerita</button>
    </form>
</section>
@endsection