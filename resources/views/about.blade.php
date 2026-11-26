@extends('layouts.app')

@section('title', 'Tentang Kami — Bali Culinary')

@section('content')
    <header class="bali-hero hero-home">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>Tentang <span class="accent">Bali Culinary</span></h1>
                <p>Merawat warisan rasa dan tradisi dapur masyarakat Bali dari generasi ke generasi — dari rasa ke hati.</p>
                <div class="bali-hero-actions">
                    <a href="{{ route('explore') }}" class="btn-terracotta">Jelajah Resep &rarr;</a>
                </div>
            </div>
        </div>
    </header>

    <section class="home-section">
        <div class="recipe-container">
            <div class="stories-grid" style="margin-bottom: 26px;">
                <article class="story-card">
                    <h3>Cerita di Balik Bali Culinary</h3>
                    <p>Setiap masakan tradisional Bali menyimpan filosofi keseimbangan rasa dan kebersamaan. Dari racikan <em>base genep</em> hingga jajanan pasar pagi hari, resep-resep ini adalah kekayaan budaya yang layak didokumentasikan dan dijaga bersama.</p>
                </article>
                <article class="story-card">
                    <h3>Misi Kami</h3>
                    <p>Menghubungkan pecinta kuliner dengan resep autentik dari 9 kabupaten/kota di Bali, memudahkan pencarian resep berdasarkan daerah maupun tingkat kesulitan, serta membuka ruang berbagi resep keluarga bagi seluruh komunitas.</p>
                </article>
            </div>

            <div class="home-cta-banner">
                <h2>Mari lestarikan kuliner bali bersama Bali Culinary</h2>
                <p>Punya resep keluarga andalan khas Bali? Bagikan resepmu dan bantu melestarikan kekayaan kuliner Pulau Dewata.</p>
                <a href="{{ route('recipe.create') }}" class="btn-terracotta sm">+ Bagikan Resep</a>
            </div>
        </div>
    </section>
@endsection
