<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderSetor;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Aktivitas: daftar pesanan belanja/jualan, setor sampah, dan draft (ui/aktivitas.html). */
class AktivitasController extends Controller
{
    private const BERJALAN = [Order::STATUS_MENUNGGU_BAYAR, Order::STATUS_DIBAYAR, Order::STATUS_DISERAHKAN];
    private const SETOR_BERJALAN = [OrderSetor::STATUS_MENUNGGU, OrderSetor::STATUS_DITERIMA, OrderSetor::STATUS_DITIMBANG];

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

            $orderItems = $orders->map(fn ($o) => [
                'jenis' => (int) $o->buyer_id === (int) $user->id ? 'belanja' : 'jual',
                'waktu' => $o->created_at, 'order' => $o,
            ]);

            $setorItems = collect();
            if (in_array($f, [null, 'setor'])) {
                $setorStatus = $tab === 'berjalan' ? self::SETOR_BERJALAN : [OrderSetor::STATUS_SELESAI, OrderSetor::STATUS_DIBATALKAN];
                $setors = OrderSetor::where('user_id', $user->id)
                    ->whereIn('status', $setorStatus)
                    ->with('mitra', 'category')
                    ->latest()
                    ->get();
                $setorItems = $setors->map(fn ($s) => [
                    'jenis' => 'setor',
                    'waktu' => $s->created_at,
                    'order_setor' => $s,
                ]);
            }

            $item = $orderItems->concat($setorItems)->sortByDesc('waktu');
        }

        return view('aktivitas', [
            'tab'  => $tab,
            'f'    => $f,
            'grup' => $item->groupBy(fn ($i) => $i['waktu']->toDateString()),
        ]);
    }
}
