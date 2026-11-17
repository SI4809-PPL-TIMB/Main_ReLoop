<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
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

        // Muat hanya relasi yang dipakai tampilan (kabupaten/provinsi tidak ditampilkan).
        // Setiap query ke DB remote ~60 ms, jadi relasi yang sama dipakai ulang, bukan diambil lagi.
        $listing->loadMissing(['photos', 'category', 'kelurahan.kecamatan', 'user.profile']);
        $penjual = $listing->user?->profile;
        if ($penjual) {
            $penjual->kelurahan_id !== null && $penjual->kelurahan_id === $listing->kelurahan_id
                ? $penjual->setRelation('kelurahan', $listing->kelurahan)
                : $penjual->loadMissing('kelurahan.kecamatan');
        }

        // Jarak perkiraan: rumus sama dengan Listing::scopeTerdekat(), dihitung dari profil penjual yang sudah dimuat.
        $profil = $request->user()?->profile;
        if ($profil?->latitude !== null && $profil->longitude !== null
            && $penjual?->latitude !== null && $penjual->longitude !== null) {
            $skala = cos(deg2rad($profil->latitude)) ** 2;
            $listing->setAttribute('jarak_derajat2',
                ($penjual->latitude - $profil->latitude) ** 2 + ($penjual->longitude - $profil->longitude) ** 2 * $skala);
        }

        return view('barang.detail', ['listing' => $listing, 'serupa' => $this->serupa($listing, $profil)]);
    }

    /**
     * Barang serupa: sekategori dulu (maks 6); bila kurang dari 3, ditambah barang terdekat lain sampai 6.
     * Satu query (sekategori diurutkan di depan) menggantikan dua query terpisah dengan hasil yang sama.
     */
    private function serupa(Listing $listing, $profil): Collection
    {
        $kategori = $listing->category_id;
        $hasil = Listing::aktif()
            ->where('listings.id', '!=', $listing->id)
            ->when($kategori, fn ($q) => $q->orderByRaw('CASE WHEN listings.category_id = ? THEN 0 ELSE 1 END', [$kategori]))
            ->terdekat($profil)
            ->limit(6)
            ->get();

        if ($kategori && $hasil->where('category_id', $kategori)->count() >= 3) {
            $hasil = $hasil->where('category_id', $kategori)->values();
        }

        // Barang milik penjual/kelurahan yang sama memakai relasi yang sudah dimuat.
        foreach ($hasil as $s) {
            if ($s->user_id === $listing->user_id && $listing->user) {
                $s->setRelation('user', $listing->user);
            }
            if ($s->kelurahan_id !== null && $s->kelurahan_id === $listing->kelurahan_id) {
                $s->setRelation('kelurahan', $listing->kelurahan);
            }
        }

        return $hasil->loadMissing(['primaryPhoto', 'user.profile', 'kelurahan']);
    }
}

