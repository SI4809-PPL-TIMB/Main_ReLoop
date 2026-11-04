<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 5.2 — Tampilan profil publik penjual (berdasarkan penjual.html).
 */
class PenjualController extends Controller
{
    public function show(Request $request, User $user): View
    {
        $user->loadMissing(['profile.kelurahan.kecamatan.kabupaten.provinsi']);

        $profil = $user->profile;
        $kel = $profil?->kelurahan;
        $kec = $kel?->kecamatan;
        $kab = $kec?->kabupaten;

        $daerah = collect([$kel?->nama, $kec?->nama, $kab?->nama])->filter()->implode(', ');

        // Hitung jarak dari pembeli login ke penjual
        $jarak = null;
        $pembeliProfil = $request->user()?->profile;
        if ($pembeliProfil?->latitude !== null && $pembeliProfil->longitude !== null &&
            $profil?->latitude !== null && $profil->longitude !== null) {
            $lat1 = $pembeliProfil->latitude;
            $lon1 = $pembeliProfil->longitude;
            $lat2 = $profil->latitude;
            $lon2 = $profil->longitude;
            $skala = cos(deg2rad($lat1)) ** 2;
            $d2 = ($lat2 - $lat1) ** 2 + ($lon2 - $lon1) ** 2 * $skala;
            $jarak = sqrt($d2) * 111.195;
        }

        // Ambil barang-barang aktif milik penjual ini
        $barang = Listing::with('primaryPhoto', 'kelurahan')
            ->where('user_id', $user->id)
            ->aktif()
            ->latest()
            ->get();

        return view('penjual.show', compact('user', 'profil', 'daerah', 'jarak', 'barang'));
    }
}

