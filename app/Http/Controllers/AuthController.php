<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
        ], [
            'identifier.required' => 'Email wajib diisi.',
            'identifier.email' => 'Masukkan alamat email yang valid.',
            'identifier.max' => 'Email maksimal 255 karakter.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.string' => 'Kata sandi harus berupa teks.',
            'password.max' => 'Kata sandi maksimal 1024 karakter.',
        ]);

        $identifier = trim($data['identifier']);
        if (! Auth::attempt(['email' => $identifier, 'password' => $data['password']])) {
            throw ValidationException::withMessages([
                'identifier' => 'Email atau kata sandi salah.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
