<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 5.2.2 — Tampil detail satu barang dan kartu penjualnya.
 */
class BarangDetailController extends Controller
{
    public function show(Request $request, Listing $listing): View
    {
        // Draft dan dihapus tidak tayang untuk siapa pun (termasuk pemilik)
        if (in_array($listing->status, [Listing::DRAFT, Listing::DIHAPUS])) {
            abort(404);
        }

        // Muat semua relasi yang dibutuhkan tampilan sekaligus
        $listing->loadMissing([
            'photos',
            'category',
            'kelurahan.kecamatan.kabupaten.provinsi',
            'user.profile.kelurahan.kecamatan',
        ]);

        // Hitung jarak perkiraan bila pembeli punya koordinat
        $profil = $request->user()?->profile;

        if ($profil?->latitude !== null && $profil->longitude !== null) {
            // Gunakan scope terdekat untuk memperoleh atribut jarak_derajat2
            $withJarak = Listing::where('listings.id', $listing->id)
                ->terdekat($profil)
                ->first();

            // Salin atribut jarak ke objek listing yang sudah lengkap relasinya
            if ($withJarak) {
                $listing->setAttribute('jarak_derajat2', $withJarak->getRawOriginal('jarak_derajat2') ?? $withJarak->attributes['jarak_derajat2'] ?? null);
            }
        }

        // Ambil barang serupa di sekitar untuk rekomendasi
        $serupa = Listing::with(['primaryPhoto', 'user.profile', 'kelurahan'])
            ->aktif()
            ->where('listings.id', '!=', $listing->id)
            ->when($listing->category_id, fn ($q) => $q->where('listings.category_id', $listing->category_id))
            ->terdekat($profil)
            ->limit(6)
            ->get();

        if ($serupa->count() < 3) {
            $lainnya = Listing::with(['primaryPhoto', 'user.profile', 'kelurahan'])
                ->aktif()
                ->where('listings.id', '!=', $listing->id)
                ->whereNotIn('listings.id', $serupa->pluck('id'))
                ->terdekat($profil)
                ->limit(6 - $serupa->count())
                ->get();
            $serupa = $serupa->concat($lainnya);
        }

        return view('barang.detail', compact('listing', 'serupa'));
    }
}

