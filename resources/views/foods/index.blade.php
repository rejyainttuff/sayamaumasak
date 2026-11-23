<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kuliner — SayaMauMasak</title>
</head>
<body>
    <h1>Daftar Kuliner</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <div>
        <a href="{{ route('foods.create') }}">+ Tambah Kuliner</a>
        <a href="{{ route('explore') }}">Ke Halaman Explore</a>
    </div>

    <br>

    @if ($foods->isEmpty())
        <p>Belum ada data kuliner yang tersedia.</p>
    @else
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Thumbnail</th>
                    <th>Nama Kuliner</th>
                    <th>Kategori</th>
                    <th>Asal Daerah</th>
                    <th>Deskripsi Singkat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($foods as $index => $food)
                    <tr>
                        <td>{{ $foods->firstItem() + $index }}</td>
                        <td>
                            @if ($food->thumbnail)
                                <img src="{{ $food->thumbnail }}" alt="{{ $food->name }}" width="80">
                            @else
                                <span>-</span>
                            @endif
                        </td>
                        <td>{{ $food->name }}</td>
                        <td>{{ $food->category?->name ?? '-' }}</td>
                        <td>{{ $food->origin_area }}</td>
                        <td>{{ $food->short_description }}</td>
                        <td>
                            <a href="{{ route('foods.edit', $food) }}">Edit</a>
                            <form action="{{ route('foods.destroy', $food) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus kuliner ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <br>

        <div>
            {{ $foods->links() }}
        </div>
    @endif
</body>
</html>