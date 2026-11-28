@extends('layouts.app')

@section('title', 'Tentang Kami — Bali Culinary')

@section('content')
    <header class="bali-hero hero-beranda">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>Tentang <span class="accent">Bali Culinary</span></h1>
                <p>Merawat warisan rasa dan tradisi dapur masyarakat Bali dari generasi ke generasi — dari rasa ke hati.</p>
                <div class="bali-hero-actions">
                    <a href="{{ route('explore') }}" class="btn-terracotta">Mulai Jelajahi &rarr;</a>
                </div>
            </div>
        </div>
    </header>

    <section class="home-section about-interactive-section">
        <div class="about-expand-backdrop" id="about-expand-backdrop" aria-hidden="true"></div>

        <div class="recipe-container">
            <p style="font-size: 13px; color: var(--muted); margin: 0 0 18px;">
                Klik salah satu kartu di bawah untuk membaca informasi lengkap secara mendalam.
            </p>

            {{-- 2 Top Cards matching about-desain.png --}}
            <div class="about-cards-grid">
                <article class="about-expand-card" data-expand-card tabindex="0" role="button" aria-expanded="false">
                    <div class="about-card-head">
                        <h3>Cerita di Balik Bali Culinary</h3>
                        <button type="button" class="about-card-close" data-expand-close aria-label="Tutup kartu">&times;</button>
                    </div>
                    <p class="about-card-summary">
                        Setiap masakan tradisional Bali menyimpan filosofi keseimbangan rasa dan kebersamaan. Dari racikan <em>base genep</em> hingga jajanan pasar pagi hari, resep-resep ini adalah kekayaan budaya yang layak didokumentasikan dan dijaga bersama.
                    </p>
                    <span class="about-card-hint">Lihat selengkapnya &rarr;</span>

                    <div class="about-card-full">
                        <p>
                            Bali Culinary Guide hadir sebagai ruang arsip digital untuk mendokumentasikan kuliner tradisional dari 9 kabupaten/kota di Bali — mulai dari hidangan upacara adat, olahan rempah <em>base genep</em>, hingga jajanan pasar pagi hari.
                        </p>
                        <p>
                            Setiap resep dan cerita kuliner yang tayang dikurasi melalui proses peninjauan agar keaslian bahan, teknik memasak, dan nilai budayanya tetap terjaga bagi generasi mendatang.
                        </p>
                        <div class="about-card-full-actions">
                            <button type="button" class="pill-tag" data-expand-close>Tutup Detail</button>
                        </div>
                    </div>
                </article>

                <article class="about-expand-card" data-expand-card tabindex="0" role="button" aria-expanded="false">
                    <div class="about-card-head">
                        <h3>About &mdash; Visi &amp; Misi</h3>
                        <button type="button" class="about-card-close" data-expand-close aria-label="Tutup kartu">&times;</button>
                    </div>
                    <p class="about-card-summary">
                        Menghubungkan pecinta kuliner dengan resep autentik dari 9 kabupaten/kota di Bali, memudahkan pencarian resep berdasarkan daerah maupun tingkat kesulitan, serta membuka ruang berbagi resep keluarga bagi seluruh komunitas.
                    </p>
                    <span class="about-card-hint">Lihat selengkapnya &rarr;</span>

                    <div class="about-card-full">
                        <div class="about-vm-grid">
                            <div>
                                <h4>Visi</h4>
                                <p>Menjadi panduan digital kuliner tradisional Bali yang terpercaya, mudah diakses, dan menjadi jembatan pelestarian warisan rasa Pulau Dewata.</p>
                            </div>
                            <div>
                                <h4>Misi</h4>
                                <ul>
                                    <li>Menghubungkan pecinta kuliner dengan resep autentik dari 9 kabupaten/kota di Bali.</li>
                                    <li>Memudahkan pencarian berdasarkan kategori, asal daerah, dan tingkat kesulitan.</li>
                                    <li>Membuka ruang berbagi resep keluarga dan cerita kuliner bagi seluruh komunitas.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="about-card-full-actions">
                            <button type="button" class="pill-tag" data-expand-close>Tutup Detail</button>
                        </div>
                    </div>
                </article>
            </div>

            {{-- Bottom Preservation Card matching about-desain.png --}}
            <article class="about-expand-card about-cta-expand-card" data-expand-card tabindex="0" role="button" aria-expanded="false" style="margin-top: 24px;">
                <div class="about-card-head" style="justify-content: center;">
                    <h2 style="margin: 0; font-family: 'Playfair Display', serif; color: var(--terracotta);">Mari lestarikan kuliner Bali bersama Bali Culinary</h2>
                    <button type="button" class="about-card-close" data-expand-close aria-label="Tutup kartu">&times;</button>
                </div>
                <p class="about-card-summary" style="text-align: center; max-width: 680px; margin: 10px auto 0;">
                    Punya resep masakan tradisional khas Bali atau cerita dapur keluarga? Bagikan sekarang atau ajukan diri menjadi Contributor agar cita rasa Pulau Dewata tetap hidup.
                </p>
                <span class="about-card-hint" style="display: block; text-align: center;">Klik untuk cara berkontribusi &rarr;</span>

                <div class="about-card-full" style="text-align: center;">
                    <p style="max-width: 640px; margin: 0 auto 16px;">
                        Kamu dapat mulai dengan membuat akun, mengajukan izin kirim resep melalui halaman Contributor, serta membagikan resep keluarga autentik yang akan ditinjau oleh tim kami.
                    </p>
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <a href="{{ route('recipe.create') }}" class="btn-terracotta sm">+ Bagikan Resep</a>
                        <a href="{{ auth()->check() ? route('contributor.apply') : route('register') }}" class="pill-tag">Ajukan Jadi Contributor</a>
                        <button type="button" class="btn-white" style="height: 36px;" data-expand-close>Tutup</button>
                    </div>
                </div>
            </article>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const cards = document.querySelectorAll('[data-expand-card]');
            const backdrop = document.getElementById('about-expand-backdrop');
            let activeCard = null;
            let activePlaceholder = null;
            let activeOriginalParent = null;
            let activeNextSibling = null;

            // Portal backdrop to body so no ancestor stacking context can trap it
            if (backdrop && backdrop.parentNode !== document.body) {
                document.body.appendChild(backdrop);
            }

            function closeActiveCard() {
                if (!activeCard) {
                    return;
                }
                const cardToRestore = activeCard;
                cardToRestore.classList.remove('is-expanded');
                cardToRestore.setAttribute('aria-expanded', 'false');

                if (activePlaceholder && activePlaceholder.parentNode) {
                    activePlaceholder.parentNode.insertBefore(cardToRestore, activePlaceholder);
                    activePlaceholder.remove();
                } else if (activeOriginalParent) {
                    if (activeNextSibling && activeNextSibling.parentNode === activeOriginalParent) {
                        activeOriginalParent.insertBefore(cardToRestore, activeNextSibling);
                    } else {
                        activeOriginalParent.appendChild(cardToRestore);
                    }
                }

                activeCard = null;
                activePlaceholder = null;
                activeOriginalParent = null;
                activeNextSibling = null;

                if (backdrop) {
                    backdrop.classList.remove('is-visible');
                    backdrop.setAttribute('aria-hidden', 'true');
                }
                document.body.classList.remove('about-card-open');
                document.body.style.overflow = '';
            }

            function openCard(card) {
                if (activeCard === card) {
                    return;
                }
                closeActiveCard();

                // Reserve space in grid so layout behind blur stays intact
                activeOriginalParent = card.parentNode;
                activeNextSibling = card.nextSibling;
                activePlaceholder = document.createElement('div');
                activePlaceholder.className = 'about-card-placeholder';
                activePlaceholder.style.minHeight = card.offsetHeight + 'px';
                activePlaceholder.setAttribute('aria-hidden', 'true');
                activeOriginalParent.insertBefore(activePlaceholder, card);

                // Portal expanded card directly to body ABOVE backdrop so it is never blurred or clipped
                document.body.appendChild(card);

                activeCard = card;
                if (backdrop) {
                    backdrop.classList.add('is-visible');
                    backdrop.setAttribute('aria-hidden', 'false');
                }
                document.body.classList.add('about-card-open');
                document.body.style.overflow = 'hidden';

                window.requestAnimationFrame(function () {
                    card.classList.add('is-expanded');
                    card.setAttribute('aria-expanded', 'true');
                });
            }

            cards.forEach(function (card) {
                card.addEventListener('click', function (event) {
                    if (event.target.closest('a')) {
                        return;
                    }
                    if (event.target.closest('[data-expand-close]')) {
                        event.stopPropagation();
                        closeActiveCard();
                        return;
                    }
                    if (!card.classList.contains('is-expanded')) {
                        openCard(card);
                    }
                });

                card.addEventListener('keydown', function (event) {
                    if ((event.key === 'Enter' || event.key === ' ') && !card.classList.contains('is-expanded')) {
                        event.preventDefault();
                        openCard(card);
                    }
                });
            });

            if (backdrop) {
                backdrop.addEventListener('click', closeActiveCard);
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeActiveCard();
                }
            });
        })();
    </script>
@endpush
