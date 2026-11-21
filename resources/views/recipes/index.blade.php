<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Recipe</title>
</head>
<body>
    <h1>Daftar Resep</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @auth
        @if (auth()->user()->isAdmin() || auth()->user()->isHelper())
            <a href="{{ route('recipes.create') }}">+ Tambah Resep</a>
        @endif
    @endauth

    <ul>
        @forelse ($recipes as $recipe)
            <li>
                <strong>{{ $recipe->title }}</strong>
                @if ($recipe->food)
                    — {{ $recipe->food->name }}
                @endif

                @auth
                    @if (auth()->user()->isAdmin() || auth()->user()->isHelper())
                        <span>[Status: {{ strtoupper($recipe->approval_status) }}]</span>
                        <a href="{{ route('recipes.edit', $recipe) }}">Edit</a>
                    @endif

                    @if (auth()->user()->isAdmin() && $recipe->isPending())
                        <form action="{{ route('recipes.approve', $recipe) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit">Approve</button>
                        </form>

                        <form action="{{ route('recipes.reject', $recipe) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit">Reject</button>
                        </form>
                    @endif
                @endauth
            </li>
        @empty
            <li>Belum ada resep yang tersedia.</li>
        @endforelse
    </ul>
</body>
</html>