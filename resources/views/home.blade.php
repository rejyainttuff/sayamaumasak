@extends('layouts.app')

@section('title', 'Beranda — Bali Culinary | Dari Rasa Ke Hati')

@section('content')
    <header class="bali-hero hero-home">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>Dari Rasa<br>Ke <span class="accent">Hati</span></h1>
                <p>Jelajahi kekayaan cita rasa kuliner tradisional Bali — dari racikan bumbu base genep hingga sajian hangat yang menyatukan keluarga.</p>
                <div class="bali-hero-actions">
                    <a href="{{ route('explore') }}" class="btn-terracotta">Lihat Resep &rarr;</a>
                </div>
            </div>
        </div>
    </header>

    <section class="home-section">
        <div class="recipe-container">
            <div class="section-heading-row">
                <span class="starburst-icon" aria-hidden="true">&#9733;</span>
                <h2 class="section-heading">Lagi Populer Lho</h2>
            </div>

            <div class="home-popular-row">
                @foreach (array_slice($popularFoods, 0, 4) as $item)
                    <a href="{{ route('food.detail', $item['slug']) }}" class="home-popular-card">
                        <div class="home-popular-thumb">
                            @if (file_exists(public_path($item['image'])))
                                <img src="{{ asset($item['image']) }}" alt="{{ $item['name'] }}">
                            @else
                                <span>{{ $item['name'] }}</span>
                            @endif
                        </div>
                        <div class="home-popular-info">
                            <h3>{{ $item['name'] }}</h3>
                            <p>{{ $item['description'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="home-cta-banner">
                <h2>Mari lestarikan kuliner bali bersama Bali Culinary</h2>
                <p>Punya resep masakan tradisional khas Bali atau cerita dapur keluarga? Bagikan sekarang agar cita rasa warisan Pulau Dewata tetap hidup.</p>
                <a href="{{ route('recipe.create') }}" class="btn-terracotta sm">+ Bagikan Resep</a>
            </div>
        </div>
    </section>
@endsection
