@extends('layouts.app')

@section('title', 'Cerita Kuliner Bali — SayaMauMasak')

@section('content')
    <header class="recipe-hero">
        <div class="recipe-hero-inner">
            <div class="recipe-hero-content">
                <a href="{{ route('home') }}" class="recipe-back-pill">&larr; Beranda</a>
                <h1>Cerita <span class="accent">Kuliner</span></h1>
                <p>Kisah, tradisi, dan filosofi di balik bumbu serta hidangan khas yang hidup di dapur masyarakat Bali.</p>
            </div>
        </div>
    </header>

    <section class="section-block">
        <div class="recipe-container">
            <div class="section-header">
                <div class="section-title-group">
                    <span class="starburst-badge" aria-hidden="true">&#10022;</span>
                    <h2 class="section-title">Kisah dari Dapur Bali</h2>
                </div>
                <a href="{{ route('explore') }}" class="btn-terracotta">Jelajah Resep &rarr;</a>
            </div>

            <div class="stories-grid">
                @foreach ($stories as $story)
                    <article class="story-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                            <span class="recipe-badge">{{ $story['region'] }}</span>
                            <span style="font-size: 12px; color: var(--muted);">{{ $story['read_time'] }}</span>
                        </div>
                        <h3>{{ $story['title'] }}</h3>
                        <p>{{ $story['excerpt'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection