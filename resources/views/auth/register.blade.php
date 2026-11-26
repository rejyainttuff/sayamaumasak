@extends('layouts.app')

@section('title', 'Daftar Akun — SayaMauMasak')

@section('content')
<div style="max-width: 460px; margin: 40px auto; padding: 24px; background: #fffaf4; border: 1px solid #e6dccf; border-radius: 16px;">
    <h1 style="margin: 0 0 8px; font-family: 'Playfair Display', serif; font-size: 28px; color: #2b1e16;">
        Daftar Akun
    </h1>
    <p style="margin: 0 0 20px; font-size: 14px; color: #6e5d52;">
        Buat akun baru untuk menyimpan resep favorit dan menulis ulasan.
    </p>

    @if ($errors->any())
        <div style="background: #fdecea; color: #b71c1c; border: 1px solid #f5c6cb; padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 13px;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('register.post') }}" method="POST">
        @csrf

        <div style="margin-bottom: 14px;">
            <label for="name" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: #2b1e16;">
                Nama Lengkap
            </label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                   style="width: 100%; padding: 10px 12px; border: 1px solid #cbb9a5; border-radius: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 14px;">
            <label for="email" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: #2b1e16;">
                Email
            </label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   style="width: 100%; padding: 10px 12px; border: 1px solid #cbb9a5; border-radius: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 14px;">
            <label for="password" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: #2b1e16;">
                Password
            </label>
            <input id="password" type="password" name="password" required
                   style="width: 100%; padding: 10px 12px; border: 1px solid #cbb9a5; border-radius: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 18px;">
            <label for="password_confirmation" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: #2b1e16;">
                Konfirmasi Password
            </label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                   style="width: 100%; padding: 10px 12px; border: 1px solid #cbb9a5; border-radius: 8px; box-sizing: border-box;">
        </div>

        <button type="submit"
                style="width: 100%; background: #a84325; color: #fff; border: none; padding: 11px 16px; border-radius: 999px; font-weight: 600; font-size: 14px; cursor: pointer;">
            Daftar Sekarang
        </button>
    </form>

    <p style="margin: 16px 0 0; text-align: center; font-size: 13px; color: #6e5d52;">
        Sudah punya akun?
        <a href="{{ route('login') }}" style="color: #a84325; font-weight: 600; text-decoration: none;">
            Masuk di sini
        </a>
    </p>
</div>
@endsection