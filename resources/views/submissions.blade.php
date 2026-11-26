@extends('layouts.app')

@section('title', 'Status Pengiriman Saya – SayaMauMasak')

@section('content')
<section class="recipe-container" style="margin-top: 24px; margin-bottom: 40px;">
    <h1>Status Pengiriman Saya</h1>

    @if (session('success'))
        <div class="alert-banner success">{{ session('success') }}</div>
    @endif

    <h2>Makanan</h2>
    @forelse ($foodSubmissions as $item)
        @php $status = $item->approval_status ?? 'pending'; @endphp
        <div style="border-top: 1px solid #e5e7eb; padding: 10px 0;">
            <strong>{{ $item->name }}</strong>
            <span>&mdash; Status: <span class="status-pill {{ $status }}">{{ ucfirst($status) }}</span></span>
        </div>
    @empty
        <p>Belum ada pengiriman makanan.</p>
    @endforelse

    <h2 style="margin-top: 24px;">Resep</h2>
    @forelse ($recipeSubmissions as $item)
        @php $status = $item->approval_status ?? 'pending'; @endphp
        <div style="border-top: 1px solid #e5e7eb; padding: 10px 0;">
            <strong>{{ $item->title }}</strong>
            <span>&mdash; Status: <span class="status-pill {{ $status }}">{{ ucfirst($status) }}</span></span>
        </div>
    @empty
        <p>Belum ada pengiriman resep.</p>
    @endforelse

    <h2 style="margin-top: 24px;">Culinary Stories</h2>
    @forelse ($storySubmissions as $item)
        @php $status = $item->approval_status ?? 'pending'; @endphp
        <div style="border-top: 1px solid #e5e7eb; padding: 10px 0;">
            <strong>{{ $item->title }}</strong>
            <span>&mdash; Status: <span class="status-pill {{ $status }}">{{ ucfirst($status) }}</span></span>
        </div>
    @empty
        <p>Belum ada pengiriman cerita kuliner.</p>
    @endforelse
</section>
@endsection