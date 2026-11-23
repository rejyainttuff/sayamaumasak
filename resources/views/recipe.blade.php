@extends('layouts.app')

@section('title', 'Aneka Resep Bali — SayaMauMasak')

@section('content')
    <header class="recipe-hero">
        <div class="recipe-hero-inner">
            <div class="recipe-hero-content">
                <h1>Dari Rasa<br>Ke <span class="accent">Hati</span></h1>
                <p>Kumpulan aneka resep masakan, minuman segar, dan jajanan pasar khas Bali yang mudah dipraktikkan di rumah.</p>
            </div>
        </div>
    </header>

    <section class="section-block">
        <div class="recipe-container">
            <div class="section-header">
                <div class="section-title-group">
                    <span class="starburst-badge" aria-hidden="true">&#10022;</span>
                    <h2 class="section-title">Aneka Resep</h2>
                </div>
                <a href="{{ route('recipe.create') }}" class="btn-terracotta rect">+ Tambah Resep</a>
            </div>

            <div class="category-tabs-wrap">
                <div class="category-tabs" role="tablist" aria-label="Kategori resep">
                    <a href="{{ route('recipe') }}" class="category-tab {{ $selectedCategory === '' ? 'active' : '' }}">
                        <span aria-hidden="true">&#127869;</span>
                        <span>Semua</span>
                    </a>
                    @foreach ($categories as $category)
                        <a
                            href="{{ route('recipe', ['category' => $category]) }}"
                            class="category-tab {{ $selectedCategory === $category ? 'active' : '' }}"
                        >
                            <span aria-hidden="true">
                                @if ($category === 'Makanan')
                                    &#127858;
                                @elseif ($category === 'Minuman')
                                    &#129380;
                                @else
                                    &#127854;
                                @endif
                            </span>
                            <span>{{ $category }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            @if (count($foods) > 0)
                <div class="recipe-grid cols-3">
                    @foreach ($foods as $food)
                        @include('partials.recipe-card', ['food' => $food])
                    @endforeach
                </div>

                <nav class="recipe-pagination" aria-label="Navigasi halaman">
                    <a
                        href="{{ route('recipe', array_filter(['category' => $selectedCategory, 'page' => max(1, $currentPage - 1)])) }}"
                        class="page-dot {{ $currentPage <= 1 ? 'disabled' : '' }}"
                        aria-label="Halaman sebelumnya"
                    >&lsaquo;</a>

                    @for ($p = 1; $p <= $totalPages; $p++)
                        <a
                            href="{{ route('recipe', array_filter(['category' => $selectedCategory, 'page' => $p])) }}"
                            class="page-dot {{ $currentPage === $p ? 'active' : '' }}"
                        >{{ $p }}</a>
                    @endfor

                    <a
                        href="{{ route('recipe', array_filter(['category' => $selectedCategory, 'page' => min($totalPages, $currentPage + 1)])) }}"
                        class="page-dot {{ $currentPage >= $totalPages ? 'disabled' : '' }}"
                        aria-label="Halaman berikutnya"
                    >&rsaquo;</a>
                </nav>
            @else
                <div class="recipe-empty">
                    <h3>Belum ada resep pada kategori ini</h3>
                    <p>Jadilah yang pertama membagikan resep khas Bali untuk kategori ini!</p>
                    <a href="{{ route('recipe.create') }}" class="btn-terracotta">+ Tambah Resep</a>
                </div>
            @endif
        </div>
    </section>
@endsection