<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraRequest;
use App\Models\MitraPengepul;
use App\Models\User;
use App\Models\WilayahKelurahan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** 8.1 · Admin: tambah, ubah, verifikasi, dan hapus mitra pengepul. */
class MitraController extends Controller
{
    public function index(Request $request): View
    {
        $status = array_key_exists($request->query('status'), MitraPengepul::STATUS) ? $request->query('status') : null;
        $cari = trim((string) $request->query('q'));

        $mitra = MitraPengepul::query()
            ->with(['user', 'kelurahan.kecamatan'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($cari !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('nama_usaha', 'like', "%{$cari}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$cari}%")->orWhere('email', 'like', "%{$cari}%"))))
            // yang menunggu verifikasi tampil paling atas
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [MitraPengepul::MENUNGGU])
            ->latest()
            ->get();

        return view('admin.mitra.index', [
            'mitra'  => $mitra,
            'status' => $status,
            'cari'   => $cari,
            'jumlah' => MitraPengepul::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.mitra.form', ['mitra' => new MitraPengepul(), 'kelurahan' => $this->kelurahan()]);
    }

    public function store(MitraRequest $request): RedirectResponse
    {
        $mitra = DB::transaction(function () use ($request) {
            $user = new User($request->safe()->only(['name', 'email', 'password']));
            // Akun dibuat admin: langsung aktif, tanpa OTP.
            $user->forceFill(['role' => User::PENGEPUL, 'email_verified_at' => now()])->save();

            return $user->mitra()->create($request->safe()->only(['nama_usaha', 'telepon', 'alamat', 'kelurahan_id']));
        });

        return redirect()->route('admin.mitra.show', $mitra)->with('sukses', 'Mitra ditambahkan. Statusnya menunggu verifikasi.');
    }

    public function show(MitraPengepul $mitra): View
    {
        return view('admin.mitra.show', [
            'mitra' => $mitra->load(['user', 'kelurahan.kecamatan.kabupaten', 'area', 'harga.category', 'jam', 'verifikator']),
        ]);
    }

    public function edit(MitraPengepul $mitra): View
    {
        return view('admin.mitra.form', ['mitra' => $mitra->load('user'), 'kelurahan' => $this->kelurahan()]);
    }

    public function update(MitraRequest $request, MitraPengepul $mitra): RedirectResponse
    {
        DB::transaction(function () use ($request, $mitra) {
            $akun = $request->safe()->only(['name', 'email']);
            if ($request->filled('password')) {
                $akun['password'] = $request->validated('password');
            }
            $mitra->user->update($akun);
            $mitra->update($request->safe()->only(['nama_usaha', 'telepon', 'alamat', 'kelurahan_id']));
        });

        return redirect()->route('admin.mitra.show', $mitra)->with('sukses', 'Data mitra diperbarui.');
    }

    public function verifikasi(Request $request, MitraPengepul $mitra): RedirectResponse
    {
        $data = $request->validate([
            'status'  => ['required', Rule::in(array_keys(MitraPengepul::STATUS))],
            'catatan' => ['nullable', 'required_if:status,'.MitraPengepul::DITOLAK, 'string', 'max:255'],
        ], [
            'status.in'           => 'Status verifikasi tidak valid.',
            'catatan.required_if' => 'Tulis alasan penolakan supaya pengepul tahu yang perlu diperbaiki.',
            'catatan.max'         => 'Alasan maksimal 255 karakter.',
        ]);

        $mitra->verifikasi($data['status'], $request->user(), $data['catatan'] ?? null);

        return redirect()->route('admin.mitra.show', $mitra)->with('sukses', match ($data['status']) {
            MitraPengepul::TERVERIFIKASI => 'Mitra terverifikasi dan sekarang bisa dicari pengguna.',
            MitraPengepul::DITOLAK       => 'Mitra ditolak.',
            default                      => 'Status dikembalikan ke menunggu verifikasi.',
        });
    }

    public function destroy(MitraPengepul $mitra): RedirectResponse
    {
        DB::transaction(function () use ($mitra) {
            // Akun tetap ada (riwayat transaksi aman), hanya peran pengepulnya dicabut.
            $mitra->user->forceFill(['role' => User::PENGGUNA])->save();
            $mitra->delete();
        });

        return redirect()->route('admin.mitra.index')->with('sukses', 'Mitra dihapus. Akunnya kembali menjadi pengguna biasa.');
    }

    private function kelurahan()
    {
        return WilayahKelurahan::with('kecamatan.kabupaten')->orderBy('kecamatan_id')->orderBy('nama')->get()
            ->groupBy(fn ($k) => $k->kecamatan->nama.', '.$k->kecamatan->kabupaten->nama);
    }
}
