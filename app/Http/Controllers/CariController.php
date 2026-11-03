<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CariController extends Controller
{
    private const KONDISI = [
        'seperti_baru' => ['Seperti baru', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'baik'         => ['Baik', 'bg-sky-50 text-sky-700 ring-sky-200'],
        'ada_minus'    => ['Ada minus', 'bg-amber-50 text-amber-700 ring-amber-200'],
    ];

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $profile = $request->user()?->profile;

        $barang = Listing::cari($keyword)
            ->terdekat($profile)
            ->with('user.profile', 'kelurahan', 'primaryPhoto')
            ->paginate(12)
            ->withQueryString();

        return view('cari', [
            'keyword' => $keyword,
            'barang'  => $barang,
            'kondisi' => self::KONDISI,
            'lokasi'  => $profile?->kelurahan?->nama,
        ]);
    }
}

