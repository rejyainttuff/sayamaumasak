@extends('layouts.app')

@section('title', 'Cerita Kuliner Bali — Bali Culinary')

@section('content')
    <header class="bali-hero hero-explore">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>Cerita <span class="accent">Kuliner</span></h1>
                <p>Kisah, sejarah, dan filosofi di balik rempah serta tradisi makan bersama masyarakat Pulau Dewata.</p>
            </div>
        </div>
    </header>

    <section class="home-section">
        <div class="recipe-container">
            <div class="section-heading-row">
                <span class="starburst-icon" aria-hidden="true">&#9733;</span>
                <h2 class="section-heading">Kisah dari Dapur Bali</h2>
            </div>

            <div class="stories-grid">
                @foreach ($stories as $story)
                    <article class="story-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                            <span class="pill-tag">{{ $story['region'] }}</span>
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
