@extends('layouts.app')

@section('title', 'Ajukan Jadi Contributor — Bali Culinary')

@section('content')
    <header class="bali-hero hero-create">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>Jadi <span class="accent">Contributor</span></h1>
                <p>Bantu melestarikan kuliner Bali dengan membagikan makanan, resep autentik, dan cerita budaya Pulau Dewata.</p>
                <div class="bali-hero-actions">
                    <a href="{{ route('account') }}" class="pill-tag">&larr; Kembali ke Akun</a>
                </div>
            </div>
        </div>
    </header>

    <section class="home-section">
        <div class="recipe-container" style="max-width: 740px;">
            @if ($errors->any())
                <div class="alert-banner error">
                    <ul style="margin: 0; padding-left: 18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($user->isContributor() || $user->isAdmin())
                <div class="alert-banner success">
                    Akunmu sudah terdaftar sebagai <strong>{{ ucfirst($user->role) }}</strong> (Level {{ $user->contributor_level ?? 1 }}). Kamu dapat langsung mengelola kiriman di <a href="{{ route('submissions.index') }}">My Submissions</a>.
                </div>
            @elseif ($latestRequest && $latestRequest->status === 'pending')
                <div class="alert-banner success">
                    Pengajuan Contributor kamu saat ini berstatus <strong>Pending</strong> dan sedang ditinjau oleh Admin.
                </div>
            @else
                <div class="share-form-card">
                    <h2 style="margin-top: 0;">Formulir Pengajuan Contributor</h2>
                    <p style="font-size: 13px; color: var(--muted); margin: 0 0 18px;">
                        Ceritakan motivasi, latar belakang, serta rencana kontribusimu secara lengkap (minimal <strong>200 karakter</strong>).
                    </p>

                    <form action="{{ route('contributor.submit') }}" method="POST" id="contributor-apply-form">
                        @csrf

                        <div class="form-group">
                            <label for="specialty">Bidang / Keahlian Kuliner (Opsional)</label>
                            <input type="text" id="specialty" name="specialty" class="form-control" value="{{ old('specialty') }}" placeholder="Contoh: Masakan Bali Utara, Jajanan Pasar, Base Genep">
                        </div>

                        <div class="form-group">
                            <label for="reason">Alasan Pengajuan (Wajib minimal 200 karakter)</label>
                            <textarea id="reason" name="reason" rows="6" class="form-control" minlength="200" maxlength="2000" placeholder="Jelaskan alasanmu ingin menjadi Contributor di Bali Culinary Guide, pengalaman memasak masakan Bali, dan resep/cerita apa yang ingin kamu bagikan..." required>{{ old('reason') }}</textarea>
                            <div class="char-counter-bar" id="reason-counter-bar">
                                <span id="reason-status-text">Minimal 200 karakter untuk dapat mengirim pengajuan.</span>
                                <strong id="reason-char-count">0 / 200 karakter</strong>
                            </div>
                        </div>

                        <button type="submit" id="contributor-submit-btn" class="btn-terracotta rect" disabled>Kirim Pengajuan Contributor</button>
                    </form>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const textarea = document.getElementById('reason');
            const countEl = document.getElementById('reason-char-count');
            const statusEl = document.getElementById('reason-status-text');
            const barEl = document.getElementById('reason-counter-bar');
            const submitBtn = document.getElementById('contributor-submit-btn');
            const MIN_CHARS = 200;

            if (!textarea || !countEl || !statusEl || !submitBtn) {
                return;
            }

            function updateCounter() {
                const len = textarea.value.trim().length;
                countEl.textContent = len + ' / ' + MIN_CHARS + ' karakter';

                if (len >= MIN_CHARS) {
                    statusEl.textContent = 'Persyaratan minimal karakter terpenuhi. Pengajuan siap dikirim.';
                    barEl.classList.remove('is-insufficient');
                    barEl.classList.add('is-valid');
                    submitBtn.disabled = false;
                } else {
                    const remaining = MIN_CHARS - len;
                    statusEl.textContent = 'Kurang ' + remaining + ' karakter lagi agar dapat dikirim.';
                    barEl.classList.add('is-insufficient');
                    barEl.classList.remove('is-valid');
                    submitBtn.disabled = true;
                }
            }

            textarea.addEventListener('input', updateCounter);
            updateCounter();
        })();
    </script>
@endpush
