<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
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

        $user = Auth::user();

        // Tolak login jika akun belum terverifikasi
        if ($user && $user->email_verified_at === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Buat kode OTP baru
            $otp = (string) random_int(10000, 99999);

            session([
                'otp_code'        => $otp,
                'pending_user_id' => $user->id,
                'otp_expires_at'  => now()->addMinutes(5)->timestamp,
            ]);

            RateLimiter::clear('otp-verify:' . $user->id . '|' . $request->ip());

            // Kirim kode verifikasi ke email
            Mail::send(
                ['html' => 'emails.kode', 'raw' => "Kode verifikasi ReLoop kamu: {$otp}. Berlaku 5 menit."],
                [
                    'judul'   => 'Verifikasi email kamu',
                    'pembuka' => "Halo {$user->name}, masukkan kode di bawah ini untuk mengaktifkan akun ReLoop-mu.",
                    'kode'    => $otp,
                    'berlaku' => '5 menit',
                ],
                fn ($message) => $message->to($user->email)->subject('Kode Verifikasi Akun ReLoop')
            );

            return redirect()->route('verifikasi')
                ->withErrors([
                    'otp' => 'Akun belum terverifikasi. Silakan masukkan kode verifikasi yang telah dikirim ke email Anda.',
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
