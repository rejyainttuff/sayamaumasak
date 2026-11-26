@extends('layouts.app')

@section('title', 'Resep Favorit — SayaMauMasak')

@section('content')
    <header class="recipe-hero">
        <div class="recipe-hero-inner">
            <div class="recipe-hero-content">
                <a href="{{ route('explore') }}" class="recipe-back-pill">&larr; Kembali ke Jelajah</a>
                <h1>Resep <span class="accent">Tersimpan</span></h1>
                <p>Kumpulan resep kuliner Bali yang kamu simpan untuk dicoba kembali kapan saja.</p>
            </div>
        </div>
    </header>

    <section class="section-block">
        <div class="recipe-container">
            @if (session('success'))
                <div class="alert-banner success" style="margin-bottom: 20px;">
                    {{ session('success') }}
                </div>
            @endif

            <div class="section-header">
                <div class="section-title-group">
                    <span class="starburst-badge" aria-hidden="true">&#9829;</span>
                    <h2 class="section-title">Favorit Saya</h2>
                </div>
                <a href="{{ route('explore') }}" class="btn-terracotta">Jelajahi Resep Lain &rarr;</a>
            </div>

            @if (count($foods) > 0)
                <div class="recipe-grid cols-3" id="favorites-grid">
                    @foreach ($foods as $food)
                        @include('partials.recipe-card', ['food' => $food])
                    @endforeach
                </div>
            @else
                <div class="recipe-empty" id="favorites-empty">
                    <h3>Belum ada resep favorit</h3>
                    <p>Klik tombol "Simpan Resep" di halaman detail kuliner untuk menyimpannya ke sini.</p>
                    <a href="{{ route('explore') }}" class="btn-terracotta">Mulai Jelajahi Resep</a>
                </div>
            @endif
        </div>
    </section>
@endsection