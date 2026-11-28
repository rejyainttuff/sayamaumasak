<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
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

            if ($role === 'helper') {
                return redirect()->intended('/');
            }

            return redirect()->intended('/');
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => 'Email atau password tidak sesuai.',
            ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showRegisterForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
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
            'password' => $validated['password'],
            'role' => 'user',
            'can_submit_recipes' => false,
            'contributor_level' => 1,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Registrasi berhasil! Selamat datang di Bali Culinary Guide.');
    }

    public function showChangePasswordForm(Request $request): View
    {
        $email = (string) old('email', $request->user()?->email ?? '');

        return view('auth.change-password', compact('email'));
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.exists' => 'Email tidak ditemukan pada akun terdaftar.',
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::where('email', $email)->firstOrFail();

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        if (Auth::check()) {
            return redirect()
                ->route('account')
                ->with('success', 'Password akun Anda berhasil diperbarui.');
        }

        return redirect()
            ->route('login')
            ->with('success', 'Password berhasil diperbarui. Silakan masuk dengan password baru Anda.');
    }
}
