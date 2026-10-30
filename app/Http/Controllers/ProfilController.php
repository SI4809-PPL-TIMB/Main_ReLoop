<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfilController extends Controller
{
    /**
     * Tampilkan halaman akun/profil pengguna yang sedang login.
     */
    public function show(): View
    {
        $user    = Auth::user()->load('profile.kelurahan');
        $profil  = $user->profile;

        return view('account', compact('user', 'profil'));
    }

    /**
     * Tampilkan form untuk mengubah data profil pengguna.
     */
    public function edit(): View
    {
        $user    = Auth::user()->load('profile.kelurahan');
        $profil  = $user->profile;

        return view('account-edit', compact('user', 'profil'));
    }

    /**
     * Perbarui nama tampilan, bio, dan nomor HP pengguna.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string',
                        'regex:/^[0-9]+$/',
                        'min:11',
                        'max:12',
                        'unique:users,phone,' . Auth::id()],
            'bio'   => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama tampilan wajib diisi.',
            'name.max'      => 'Nama tampilan tidak boleh lebih dari 150 karakter.',
            'phone.regex'   => 'Nomor HP hanya boleh berisi angka.',
            'phone.min'     => 'Nomor HP harus berjumlah 11 sampai 12 angka.',
            'phone.max'     => 'Nomor HP harus berjumlah 11 sampai 12 angka.',
            'phone.unique'  => 'Nomor HP ini sudah digunakan akun lain.',
            'bio.max'       => 'Bio tidak boleh lebih dari 500 karakter.',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update([
            'name'  => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if (array_key_exists('bio', $validated)) {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                ['bio' => $validated['bio']]
            );
        }

        return redirect()->route('account')
            ->with('sukses', 'Profil berhasil diperbarui.');
    }

    /**
     * Upload atau ganti foto profil (avatar) pengguna.
     *
     * Ketentuan:
     *  - Format: jpg, jpeg, png, webp
     *  - Ukuran maks: 2 MB
     *  - Disimpan di storage/app/public/avatars/{user_id}/
     *  - File lama dihapus otomatis
     */
    public function uploadAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048', // 2 MB dalam kilobytes
            ],
        ], [
            'avatar.required' => 'Pilih foto terlebih dahulu.',
            'avatar.image'    => 'File harus berupa gambar.',
            'avatar.mimes'    => 'Format foto harus jpg, jpeg, png, atau webp.',
            'avatar.max'      => 'Ukuran foto tidak boleh lebih dari 2 MB.',
        ]);

        /** @var \App\Models\User $user */
        $user   = Auth::user();
        $profil = $user->profile;

        // Hapus avatar lama jika ada dan bukan null
        if ($profil && $profil->avatar) {
            Storage::disk('public')->delete($profil->avatar);
        }

        // Simpan foto baru ke storage/app/public/avatars/{user_id}/
        $path = $request->file('avatar')->store(
            'avatars/' . $user->id,
            'public'
        );

        // Update atau buat record profil
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['avatar'  => $path]
        );

        $target = $request->header('referer') && str_contains($request->header('referer'), '/profil/ubah')
            ? 'account.edit'
            : 'account';

        return redirect()->route($target)
            ->with('sukses', 'Foto profil berhasil diperbarui.');
    }

    /**
     * Hapus foto profil dan kembalikan ke avatar inisial.
     */
    public function hapusAvatar(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user   = Auth::user();
        $profil = $user->profile;

        if ($profil && $profil->avatar) {
            Storage::disk('public')->delete($profil->avatar);
            $profil->update(['avatar' => null]);
        }

        $target = $request->header('referer') && str_contains($request->header('referer'), '/profil/ubah')
            ? 'account.edit'
            : 'account';

        return redirect()->route($target)
            ->with('sukses', 'Foto profil berhasil dihapus.');
    }
}
