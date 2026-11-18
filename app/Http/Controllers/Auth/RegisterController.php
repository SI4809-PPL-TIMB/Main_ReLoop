<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.daftar');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        // 1. Buat akun baru atau perbarui akun yang belum terverifikasi
        $user = User::where('email', $request->identifier)->first();

        if ($user && $user->email_verified_at === null) {
            $user->update([
                'name'     => $request->name,
                'password' => Hash::make($request->password),
            ]);
        } else {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->identifier,
                'password' => Hash::make($request->password),
            ]);
        }

        // 2. Buat OTP 5 digit baru
        $otp = (string) random_int(10000, 99999);

        // 3. Simpan OTP dan identitas pengguna di session
        session([
            'otp_code'        => $otp,
            'pending_user_id' => $user->id,
            'otp_expires_at'  => now()->addMinutes(5)->timestamp,
        ]);

        // Bersihkan batas throttle verifikasi untuk pengguna ini
        RateLimiter::clear('otp-verify:' . $user->id . '|' . $request->ip());

        // 4. Kirim OTP ke email
        $this->kirimKode($user, $otp, 'Kode Verifikasi Akun ReLoop');

        // Jangan login sebelum OTP terverifikasi
        return redirect()->route('verifikasi')
            ->with('success', 'Kode verifikasi telah dikirim ke email Anda!');
    }

    public function showVerification(): View
    {
        return view('auth.verifikasi');
    }

    public function storeVerification(Request $request): RedirectResponse
    {
        $userId = session('pending_user_id');
        $throttleKey = 'otp-verify:' . ($userId ?: 'guest') . '|' . $request->ip();

        // 1. Cek batas throttle percobaan memasukkan kode OTP (5 percobaan)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $message = "Terlalu banyak percobaan kode verifikasi. Coba lagi dalam {$seconds} detik.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 429, ['Retry-After' => (string) $seconds]);
            }

            return back()->withErrors([
                'otp' => $message,
            ]);
        }

        // 2. Ambil OTP dari form, baik 5 kolom maupun 1 input
        $otpInput = $request->input('otp');

        if (is_array($otpInput)) {
            $userOtp = implode('', $otpInput);
        } elseif (is_string($otpInput)) {
            $userOtp = $otpInput;
        } else {
            $userOtp = '';
        }

        // 3. Ambil data verifikasi dari session
        $sessionOtp = session('otp_code');
        $expiresAt = session('otp_expires_at');

        // 4. Pastikan session verifikasi masih tersedia
        if (
            $sessionOtp === null ||
            !$userId ||
            !$expiresAt
        ) {
            RateLimiter::hit($throttleKey, 60);

            return redirect()->route('verifikasi')
                ->withErrors([
                    'otp' => 'Sesi verifikasi berakhir. Silakan daftar kembali.',
                ]);
        }

        // 5. Periksa masa berlaku OTP
        if (now()->timestamp > $expiresAt) {
            RateLimiter::hit($throttleKey, 60);

            // Batalkan kode lama tapi pertahankan pending_user_id agar bisa kirim ulang kode tanpa terkunci
            session()->forget([
                'otp_code',
                'otp_expires_at',
            ]);

            return back()->withErrors([
                'otp' => 'Kode OTP sudah kedaluwarsa. Silakan kirim ulang kode atau daftar kembali.',
            ]);
        }

        // 6. Pastikan OTP terdiri dari 5 digit
        if (!preg_match('/^[0-9]{5}$/', $userOtp)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'otp' => 'Masukkan kode verifikasi 5 digit yang valid.',
            ]);
        }

        // 7. Cocokkan OTP
        if (!hash_equals((string) $sessionOtp, $userOtp)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'otp' => 'Kode verifikasi salah. Silakan coba lagi.',
            ]);
        }

        // 8. Cari pengguna yang sedang diverifikasi
        $user = User::find($userId);

        if (!$user) {
            RateLimiter::hit($throttleKey, 60);

            session()->forget([
                'otp_code',
                'pending_user_id',
                'otp_expires_at',
            ]);

            return redirect()->route('daftar')
                ->withErrors([
                    'otp' => 'Data pengguna tidak ditemukan. Silakan daftar kembali.',
                ]);
        }

        // Berhasil verifikasi: bersihkan throttle
        RateLimiter::clear($throttleKey);

        // 9. Tandai email terverifikasi jika kolom tersedia
        if (Schema::hasColumn('users', 'email_verified_at')) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        // 10. Login setelah OTP benar
        Auth::login($user);

        // Perbarui ID session untuk keamanan
        $request->session()->regenerate();

        // 11. Bersihkan data OTP
        session()->forget([
            'otp_code',
            'pending_user_id',
            'otp_expires_at',
        ]);

        // 12. Langkah 2 dari 2: atur lokasi
        return redirect()->route('lokasi');
    }

    public function resendVerification(): RedirectResponse
    {
        // Ambil pengguna dari session, bukan Auth
        // karena pengguna belum login sebelum verifikasi.
        $userId = session('pending_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return redirect()->route('daftar')
                ->withErrors([
                    'otp' => 'Sesi verifikasi berakhir. Silakan daftar kembali.',
                ]);
        }

        // Batasi spam kirim ulang (maksimal 3 kali per menit)
        $resendKey = 'otp-resend:' . $user->id . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($resendKey, 3)) {
            $seconds = RateLimiter::availableIn($resendKey);
            return back()->withErrors([
                'otp' => "Tunggu {$seconds} detik sebelum meminta kode baru.",
            ]);
        }
        RateLimiter::hit($resendKey, 60);

        // Buat OTP baru (membatalkan kode lama di session)
        $otp = (string) random_int(10000, 99999);

        session([
            'otp_code'       => $otp,
            'otp_expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        // Bersihkan throttle percobaan verifikasi saat kode baru dikirimkan
        RateLimiter::clear('otp-verify:' . $user->id . '|' . request()->ip());

        // Kirim OTP baru
        $this->kirimKode($user, $otp, 'Kode Verifikasi Baru ReLoop');

        return back()->with(
            'success',
            'Kode verifikasi baru telah dikirim ke email Anda!'
        );
    }

    public function showComplete(): View
    {
        return view('auth.selesai');
    }

    private function kirimKode(User $user, string $otp, string $subjek): void
    {
        Mail::send(
            ['html' => 'emails.kode', 'raw' => "Kode verifikasi ReLoop kamu: {$otp}. Berlaku 5 menit."],
            [
                'judul'   => 'Verifikasi email kamu',
                'pembuka' => "Halo {$user->name}, masukkan kode di bawah ini untuk mengaktifkan akun ReLoop-mu.",
                'kode'    => $otp,
                'berlaku' => '5 menit',
            ],
            fn ($message) => $message->to($user->email)->subject($subjek)
        );
    }
}