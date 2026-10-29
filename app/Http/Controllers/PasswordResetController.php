<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.password', ['step' => 1]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'email.max' => 'Email maksimal 255 karakter.',
        ]);

        $email = $data['email'];
        $key = $this->codeKey($email);
        if (RateLimiter::attempt($key.':send', 1, fn () => true, 60)) {
            $code = (string) random_int(100000, 999999);
            Cache::put($key, Hash::make($code), now()->addMinutes(10));
            User::where('email', $email)->first()?->notify(new PasswordResetCode($code));
        }
        $request->session()->put('password_reset_email', $email);

        // Keep the response identical for known, unknown, and recently requested accounts.
        return redirect()->route('password.sent');
    }

    public function sent(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');
        if (! is_string($email)) {
            return redirect()->route('password.request');
        }

        return view('auth.password', [
            'step' => 2,
            'email' => $email,
            'retryAfter' => RateLimiter::availableIn($this->codeKey($email).':send'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']], [
            'code.required' => 'Kode verifikasi wajib diisi.',
            'code.digits' => 'Masukkan kode 6 digit angka.',
        ]);
        $email = $request->session()->get('password_reset_email');
        if (! is_string($email)) {
            return redirect()->route('password.request');
        }
        $key = $this->codeKey($email);
        if (RateLimiter::tooManyAttempts($key.':verify', 5)) {
            throw ValidationException::withMessages(['code' => 'Terlalu banyak percobaan. Coba lagi setelah 10 menit.']);
        }
        RateLimiter::hit($key.':verify', 600);

        return Cache::lock($key.':exchange', 10)->block(5, function () use ($key, $email, $request) {
            $hash = Cache::get($key);
            $user = User::where('email', $email)->first();
            if (! is_string($hash) || ! Hash::check($request->string('code')->toString(), $hash) || ! $user) {
                throw ValidationException::withMessages(['code' => 'Kode tidak valid atau sudah kedaluwarsa.']);
            }

            Cache::forget($key);
            $request->session()->forget('password_reset_email');
            $token = Password::createToken($user);

            return redirect()->route('password.reset', ['token' => $token, 'email' => $email]);
        });
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.password', [
            'step' => 3,
            'token' => $token,
            'email' => is_string($request->query('email')) ? $request->query('email') : '',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:1024', 'confirmed'],
        ], [
            'token.required' => 'Tautan tidak valid. Silakan minta tautan baru.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.max' => 'Kata sandi maksimal 1024 karakter.',
            'password.confirmed' => 'Kata sandi belum sama.',
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Tautan tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
            ]);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('password.complete');
    }

    private function codeKey(string $email): string
    {
        return 'password-reset-code:'.hash('sha256', $email);
    }
}
