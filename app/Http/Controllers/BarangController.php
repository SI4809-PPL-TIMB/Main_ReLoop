<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingRequest;
use App\Models\Category;
use App\Models\Listing;
use App\Support\ListingPhotos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BarangController extends Controller
{
    private const TAB = ['aktif' => [Listing::AKTIF, Listing::DRAFT], 'dipesan' => [Listing::DIPESAN], 'terjual' => [Listing::TERJUAL]];

    public function index(Request $request): View
    {
        $semua = Listing::milik($request->user())->with('primaryPhoto', 'kelurahan')->latest()->get();
        $tab = array_key_exists($request->query('tab'), self::TAB) ? $request->query('tab') : 'aktif';

        return view('barang.index', [
            'tab'    => $tab,
            'jumlah' => collect(self::TAB)->map(fn ($status) => $semua->whereIn('status', $status)->count()),
            'barang' => $semua->whereIn('status', self::TAB[$tab]),
        ]);
    }

    public function create(Request $request): View
    {
        return view('barang.form', ['listing' => new Listing(['quantity' => 1, 'is_negotiable' => true]), 'kategori' => $this->kategori(), 'lokasi' => $request->user()->profile?->kelurahan?->breadcrumb()]);
    }

    public function store(ListingRequest $request): RedirectResponse
    {
        $lokasi = $request->user()->profile?->kelurahan_id;
        if (! $lokasi) {
            return back()->withInput()->withErrors(['kelurahan_id' => 'Atur lokasimu dulu supaya pembeli tahu daerah barang ini.']);
        }

        $listing = $request->user()->listings()->create($this->data($request) + [
            'kelurahan_id' => $lokasi,
            'status'       => $request->isDraft() ? Listing::DRAFT : Listing::AKTIF,
            'published_at' => $request->isDraft() ? null : now(),
        ]);
        ListingPhotos::simpan($listing, $request->file('photos', []));

        return redirect()->route('barang.index')->with('success', $request->isDraft() ? 'Draft tersimpan.' : 'Barangmu sudah tayang!');
    }

    private function kategori()
    {
        return Category::where('type', Category::TYPE_BEKAS)->orderBy('id')->get();
    }

    private function data(ListingRequest $request): array
    {
        return $request->safe()->except(['photos', 'remove_photos', 'action']) + ['quantity' => $request->integer('quantity', 1) ?: 1];
    }
}
