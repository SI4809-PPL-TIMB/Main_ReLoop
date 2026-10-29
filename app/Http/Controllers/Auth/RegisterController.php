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
        // 1. Buat akun pengguna
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->identifier,
            'password' => Hash::make($request->password),
        ]);

        // 2. Buat OTP 5 digit
        $otp = (string) random_int(10000, 99999);

        // 3. Simpan OTP dan identitas pengguna di session
        session([
            'otp_code'        => $otp,
            'pending_user_id' => $user->id,
            'otp_expires_at'  => now()->addMinutes(5)->timestamp,
        ]);

        // 4. Kirim OTP ke email
        Mail::raw(
            "Kode verifikasi ReLoop Anda adalah: {$otp}",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Kode Verifikasi Akun ReLoop');
            }
        );

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
        // 1. Ambil OTP dari form, baik 5 kolom maupun 1 input
        $otpInput = $request->input('otp');

        if (is_array($otpInput)) {
            $userOtp = implode('', $otpInput);
        } elseif (is_string($otpInput)) {
            $userOtp = $otpInput;
        } else {
            $userOtp = '';
        }

        // 2. Ambil data verifikasi dari session
        $sessionOtp = session('otp_code');
        $userId = session('pending_user_id');
        $expiresAt = session('otp_expires_at');

        // 3. Pastikan session verifikasi masih tersedia
        if (
            $sessionOtp === null ||
            !$userId ||
            !$expiresAt
        ) {
            return redirect()->route('verifikasi')
                ->withErrors([
                    'otp' => 'Sesi verifikasi berakhir. Silakan daftar kembali.',
                ]);
        }

        // 4. Periksa masa berlaku OTP
        if (now()->timestamp > $expiresAt) {
            session()->forget([
                'otp_code',
                'pending_user_id',
                'otp_expires_at',
            ]);

            return back()->withErrors([
                'otp' => 'Kode OTP sudah kedaluwarsa. Silakan daftar kembali.',
            ]);
        }

        // 5. Pastikan OTP terdiri dari 5 digit
        if (!preg_match('/^[0-9]{5}$/', $userOtp)) {
            return back()->withErrors([
                'otp' => 'Masukkan kode verifikasi 5 digit yang valid.',
            ]);
        }

        // 6. Cocokkan OTP
        if (!hash_equals((string) $sessionOtp, $userOtp)) {
            return back()->withErrors([
                'otp' => 'Kode verifikasi salah. Silakan coba lagi.',
            ]);
        }

        // 7. Cari pengguna yang sedang diverifikasi
        $user = User::find($userId);

        if (!$user) {
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

        // 8. Tandai email terverifikasi jika kolom tersedia
        if (Schema::hasColumn('users', 'email_verified_at')) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        // 9. Login setelah OTP benar
        Auth::login($user);

        // Perbarui ID session untuk keamanan
        $request->session()->regenerate();

        // 10. Bersihkan data OTP
        session()->forget([
            'otp_code',
            'pending_user_id',
            'otp_expires_at',
        ]);

        // 11. Langkah 2 dari 2: atur lokasi
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

        // Buat OTP baru
        $otp = (string) random_int(10000, 99999);

        session([
            'otp_code'       => $otp,
            'otp_expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        // Kirim OTP baru
        Mail::raw(
            "Kode verifikasi baru ReLoop Anda adalah: {$otp}",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Kode Verifikasi Baru ReLoop');
            }
        );

        return back()->with(
            'success',
            'Kode verifikasi baru telah dikirim ke email Anda!'
        );
    }

    public function showComplete(): View
    {
        return view('auth.selesai');
    }
}