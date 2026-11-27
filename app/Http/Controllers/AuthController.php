<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $role = $request->user()?->role;

            if ($role === 'admin') {
                return redirect()->intended('/admin');
            }

            if (in_array($role, ['helper', 'contributor'], true)) {
                return redirect()->intended('/recipes');
            }

            return redirect()->intended('/');
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => 'Email atau password tidak sesuai.',
            ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => ['nullable', 'string', 'max:60', 'unique:users,username'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => ! empty($validated['username']) ? ltrim(trim($validated['username']), '@') : null,
            'email' => $validated['email'],
            'password' => $validated['password'], // otomatis di-hash karena cast 'password' => 'hashed' di User.php
            'role' => 'user',
            'contributor_level' => 1,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Registrasi berhasil! Selamat datang di SayaMauMasak.');
    }
}
