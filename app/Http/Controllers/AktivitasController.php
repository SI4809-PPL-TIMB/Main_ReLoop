<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Aktivitas: daftar pesanan belanja/jualan dan draft (ui/aktivitas.html). Setor belum ada backend. */
class AktivitasController extends Controller
{
    private const BERJALAN = [Order::STATUS_MENUNGGU_BAYAR, Order::STATUS_DIBAYAR, Order::STATUS_DISERAHKAN];

    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), ['berjalan', 'riwayat', 'draft']) ? $request->query('tab') : 'berjalan';
        $f = in_array($request->query('f'), ['belanja', 'jual', 'setor']) ? $request->query('f') : null;

        $item = collect();
        if ($tab === 'draft') {
            if (in_array($f, [null, 'jual'])) {
                $item = Listing::milik($user)->where('status', Listing::DRAFT)->with('category', 'primaryPhoto')->latest()->get()
                    ->map(fn ($l) => ['jenis' => 'draft', 'waktu' => $l->updated_at, 'listing' => $l]);
            }
        } else {
            $status = $tab === 'berjalan' ? self::BERJALAN : [Order::STATUS_SELESAI, Order::STATUS_DIBATALKAN];
            $orders = Order::with('listing.category', 'listing.primaryPhoto', 'buyer', 'seller')
                ->whereIn('status', $status)
                ->where(fn ($q) => $q->when($f !== 'jual', fn ($q) => $q->orWhere('buyer_id', $user->id))
                                     ->when($f !== 'belanja', fn ($q) => $q->orWhere('seller_id', $user->id)))
                ->when($f === 'setor', fn ($q) => $q->whereRaw('1 = 0'))
                ->latest()->get();
            $item = $orders->map(fn ($o) => [
                'jenis' => (int) $o->buyer_id === (int) $user->id ? 'belanja' : 'jual',
                'waktu' => $o->created_at, 'order' => $o,
            ]);
        }

        return view('aktivitas', [
            'tab'  => $tab,
            'f'    => $f,
            'grup' => $item->groupBy(fn ($i) => $i['waktu']->toDateString()),
        ]);
    }
}
