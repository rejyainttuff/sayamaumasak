<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Resep</title>
</head>
<body>
    <h1>Daftar Resep</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @auth
        @if (auth()->user()->isAdmin() || auth()->user()->isHelper())
            <p><a href="{{ route('recipes.create') }}">+ Tambah Resep</a></p>
        @endif
    @endauth

    @forelse ($recipes as $recipe)
        <article style="border: 1px solid #ccc; padding: 12px; margin-bottom: 16px;">
            <h2>
                {{ $recipe->title }}
                @if ($recipe->food)
                    <small>— {{ $recipe->food->name }}</small>
                @endif
            </h2>

            <p>
                <strong>Persiapan:</strong> {{ $recipe->prep_time_minutes }} menit |
                <strong>Memasak:</strong> {{ $recipe->cook_time_minutes }} menit |
                <strong>Porsi:</strong> {{ $recipe->servings }} |
                <strong>Kesulitan:</strong> {{ $recipe->difficulty }}
            </p>

            @auth
                @if (auth()->user()->isAdmin() || auth()->user()->isHelper())
                    <p>
                        <strong>Status:</strong> {{ strtoupper($recipe->approval_status) }}
                        | <a href="{{ route('recipes.edit', $recipe) }}">Edit</a>
                    </p>
                @endif

                @if (auth()->user()->isAdmin() && $recipe->isPending())
                    <p>
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
                    </p>
                @endif
            @endauth

            <h3>Bahan-Bahan</h3>
            @if ($recipe->recipeIngredients->isNotEmpty())
                <ul>
                    @foreach ($recipe->recipeIngredients as $ri)
                        <li>
                            <strong>{{ $ri->ingredient->name ?? '-' }}</strong>
                            @if (!is_null($ri->amount) || !empty($ri->unit))
                                — {{ $ri->amount }} {{ $ri->unit }}
                            @endif
                            @if (!empty($ri->notes))
                                <em>({{ $ri->notes }})</em>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p><em>Belum ada data bahan.</em></p>
            @endif

            <h3>Langkah-Langkah Memasak</h3>
            @if ($recipe->recipeSteps->isNotEmpty())
                <ol>
                    @foreach ($recipe->recipeSteps as $step)
                        <li value="{{ $step->step_number }}" style="margin-bottom: 8px;">
                            <div>{{ $step->instruction }}</div>
                            @if (!empty($step->image))
                                <div style="margin-top: 4px;">
                                    <img src="{{ asset('storage/' . $step->image) }}" alt="Langkah {{ $step->step_number }}" style="max-width: 180px; height: auto;">
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @else
                <p><em>Belum ada langkah memasak.</em></p>
            @endif
        </article>
    @empty
        <p>Belum ada resep yang tersedia.</p>
    @endforelse
</body>
</html>