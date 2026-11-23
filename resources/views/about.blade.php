@extends('layouts.app')

@section('title', 'Tentang Kami — SayaMauMasak')

@section('content')
    <header class="recipe-hero">
        <div class="recipe-hero-inner">
            <div class="recipe-hero-content">
                <a href="{{ route('home') }}" class="recipe-back-pill">&larr; Beranda</a>
                <h1>Tentang <span class="accent">Kami</span></h1>
                <p>Mengenal misi SayaMauMasak dalam merawat dan membagikan kekayaan kuliner tradisional Bali dari rasa ke hati.</p>
            </div>
        </div>
    </header>

    <section class="section-block">
        <div class="recipe-container">
            <div class="detail-top-grid" style="margin-top: 0;">
                <div class="detail-card">
                    <h2>Cerita di Balik SayaMauMasak</h2>
                    <p style="margin-bottom: 14px;">
                        Setiap masakan tradisional Bali menyimpan filosofi keseimbangan rasa dan kebersamaan. Dari racikan <em>base genep</em> hingga jajanan pasar pagi hari, resep-resep ini adalah warisan budaya yang layak dijaga bersama.
                    </p>
                    <p>
                        Melalui SayaMauMasak, siapa pun dapat menjelajahi resep khas dari berbagai kabupaten di Bali, mempelajari sejarah singkat setiap hidangan, serta ikut membagikan resep keluarga kepada komunitas.
                    </p>
                </div>

                <div class="detail-card">
                    <h3>Fokus Kami</h3>
                    <ul class="ingredient-list">
                        <li>Mendokumentasikan resep autentik Bali dari 9 kabupaten/kota.</li>
                        <li>Memudahkan pencarian berdasarkan kategori, asal daerah, dan tingkat kesulitan.</li>
                        <li>Membuka ruang berbagi resep bagi masyarakat dan pecinta kuliner.</li>
                    </ul>
                </div>
            </div>

            <div class="cta-banner" style="margin-top: 32px;">
                <div>
                    <h2>Ingin ikut melestarikan resep tradisional Bali?</h2>
                    <p>Bagikan resep andalan keluargamu atau mulai eksplorasi ragam hidangan khas Pulau Dewata sekarang.</p>
                </div>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <a href="{{ route('explore') }}" class="btn-outline">Jelajah Resep</a>
                    <a href="{{ route('recipe.create') }}" class="btn-terracotta">+ Bagikan Resep</a>
                </div>
            </div>
        </div>
    </section>
@endsection