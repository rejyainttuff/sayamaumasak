@extends('layouts.app')

@section('title', 'Daftar Resep — SayaMauMasak')

@section('content')
<div style="max-width: 1040px; margin: 36px auto; padding: 0 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;">
        <div>
            <h1 style="margin: 0; font-family: 'Playfair Display', serif; font-size: 30px; color: #2b1e16;">
                Daftar Resep
            </h1>
            <p style="margin: 4px 0 0; font-size: 14px; color: #6e5d52;">
                Kelola dan tinjau resep kuliner Bali beserta bahan dan langkah memasaknya.
            </p>
        </div>

        @auth
            @if (auth()->user()->isAdmin() || auth()->user()->isHelper())
                <a href="{{ route('recipes.create') }}"
                   style="background: #a84325; color: #fff; padding: 10px 18px; border-radius: 999px; text-decoration: none; font-weight: 600; font-size: 14px;">
                    + Tambah Resep
                </a>
            @endif
        @endauth
    </div>

    @if (session('success'))
        <div style="background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif

    @forelse ($recipes as $recipe)
        <article style="background: #fffaf4; border: 1px solid #e6dccf; border-radius: 18px; padding: 22px 24px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
                <h2 style="margin: 0; font-family: 'Playfair Display', serif; font-size: 22px; color: #2b1e16;">
                    {{ $recipe->title }}
                    @if ($recipe->food)
                        <small style="font-family: inherit; font-size: 15px; font-weight: 400; color: #7a685b;">
                            — {{ $recipe->food->name }}
                        </small>
                    @endif
                </h2>

                @auth
                    @if (auth()->user()->isAdmin() || auth()->user()->isHelper())
                        <span style="background: #efe4d6; color: #6e3b1e; font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 999px;">
                            Status: {{ strtoupper($recipe->approval_status) }}
                        </span>
                    @endif
                @endauth
            </div>

            <p style="margin: 10px 0 12px; font-size: 14px; color: #5c4d42;">
                <strong>Persiapan:</strong> {{ $recipe->prep_time_minutes }} menit |
                <strong>Memasak:</strong> {{ $recipe->cook_time_minutes }} menit |
                <strong>Porsi:</strong> {{ $recipe->servings }} |
                <strong>Kesulitan:</strong> {{ $recipe->difficulty }}
            </p>

            @auth
                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 16px;">
                    @if (auth()->user()->isAdmin() || auth()->user()->isHelper())
                        <a href="{{ route('recipes.edit', $recipe) }}"
                           style="background: #ffffff; border: 1px solid #cbb9a5; color: #2b1e16; padding: 7px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600;">
                            Edit
                        </a>
                    @endif

                    @if (auth()->user()->isAdmin() && $recipe->isPending())
                        <form action="{{ route('recipes.approve', $recipe) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    style="background: #2e7d32; color: #fff; border: none; padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                Approve
                            </button>
                        </form>

                        <form action="{{ route('recipes.reject', $recipe) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    style="background: #c62828; color: #fff; border: none; padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                Reject
                            </button>
                        </form>
                    @endif
                </div>
            @endauth

            <hr style="border: none; border-top: 1px solid #ede2d5; margin: 16px 0;">

            <h3 style="margin: 0 0 8px; font-family: 'Playfair Display', serif; font-size: 18px; color: #2b1e16;">
                Bahan-Bahan
            </h3>
            @if ($recipe->recipeIngredients->isNotEmpty())
                <ul style="margin: 0 0 18px; padding-left: 20px; color: #3d3129; line-height: 1.6;">
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
                <p style="margin: 0 0 18px; color: #7a685b;"><em>Belum ada data bahan.</em></p>
            @endif

            <h3 style="margin: 0 0 8px; font-family: 'Playfair Display', serif; font-size: 18px; color: #2b1e16;">
                Langkah-Langkah Memasak
            </h3>
            @if ($recipe->recipeSteps->isNotEmpty())
                <ol style="margin: 0; padding-left: 20px; color: #3d3129; line-height: 1.6;">
                    @foreach ($recipe->recipeSteps as $step)
                        <li value="{{ $step->step_number }}" style="margin-bottom: 10px;">
                            <div>{{ $step->instruction }}</div>
                            @if (!empty($step->image))
                                <div style="margin-top: 6px;">
                                    <img src="{{ asset('storage/' . $step->image) }}"
                                         alt="Langkah {{ $step->step_number }}"
                                         style="max-width: 180px; height: auto; border-radius: 10px; border: 1px solid #e6dccf;">
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @else
                <p style="margin: 0; color: #7a685b;"><em>Belum ada langkah memasak.</em></p>
            @endif
        </article>
    @empty
        <div style="background: #fffaf4; border: 1px solid #e6dccf; border-radius: 18px; padding: 28px; text-align: center; color: #6e5d52;">
            Belum ada resep yang tersedia.
        </div>
    @endforelse
</div>
@endsection