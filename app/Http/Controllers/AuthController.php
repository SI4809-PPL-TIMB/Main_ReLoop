<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
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
        ]);

        // An IP-wide limit also prevents bypassing the limit by changing identifiers.
        $key = 'login:'.hash('sha256', $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'identifier' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        $identifier = trim($data['identifier']);
        if (! Auth::attempt(['email' => $identifier, 'password' => $data['password']])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'identifier' => 'Email atau kata sandi salah.',
            ]);
        }

        RateLimiter::clear($key);
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
