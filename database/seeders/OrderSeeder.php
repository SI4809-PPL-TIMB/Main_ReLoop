<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\WilayahKelurahan;
use Illuminate\Database\Seeder;

/**
 * 7.2.2 — Seeder pesanan dibayar (escrow), diserahkan, dan selesai untuk pengujian alur kelola pesanan (PPLTIMB-99).
 */
class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $buyer = User::where('email', 'pembeli@reloop.test')->first();
        $seller = User::where('email', 'penjual@reloop.test')->first();

        if (! $buyer || ! $seller) {
            return;
        }

        $kategoriPerabot = Category::where('slug', 'perabot')->first() ?? Category::first();
        $kelurahan = WilayahKelurahan::where('nama', 'Paledang')->first();

        // 1. Pesanan DIBAYAR (Dana Tertahan / Escrow - Siap Diserahkan Penjual)
        $listingDibayar = Listing::firstOrCreate(['user_id' => $seller->id, 'title' => 'Kursi Kayu Jati Ukir Jepara'], [
            'user_id'        => $seller->id,
            'category_id'    => $kategoriPerabot->id,
            'kelurahan_id'   => $kelurahan?->id,
            'title'          => 'Kursi Kayu Jati Ukir Jepara',
            'description'    => 'Kondisi mulus, kayu jati solid tua tanpa rayap.',
            'price'          => 320000,
            'condition'      => 'baik',
            'status'         => Listing::DIPESAN,
            'is_negotiable'  => true,
            'published_at'   => now()->subDays(2),
        ]);

        DemoListingPhotos::attach($listingDibayar, 'kursi-kayu');

        $orderDibayar = Order::firstOrCreate(['order_number' => 'RL-20261020-0142'], [
            'order_number'     => 'RL-20261020-0142',
            'listing_id'       => $listingDibayar->id,
            'buyer_id'         => $buyer->id,
            'seller_id'        => $seller->id,
            'agreed_price'     => 320000,
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'COD di Alun-Alun Bogor',
            'status'           => Order::STATUS_DIBAYAR,
            'created_at'       => now()->subHours(5),
        ]);

        Payment::firstOrCreate(['order_id' => $orderDibayar->id], [
            'order_id'     => $orderDibayar->id,
            'amount'       => 320000,
            'platform_fee' => 9600, // 3% dari 320.000
            'method'       => Payment::METHOD_VA,
            'bank'         => 'bca',
            'status'       => Payment::STATUS_BERHASIL,
            'paid_at'      => now()->subHours(4),
        ]);

        // 2. Pesanan DISERAHKAN (Menunggu Konfirmasi Pembeli)
        $listingDiserahkan = Listing::firstOrCreate(['user_id' => $seller->id, 'title' => 'Meja Belajar Minimalis Kayu Pinus'], [
            'user_id'        => $seller->id,
            'category_id'    => $kategoriPerabot->id,
            'kelurahan_id'   => $kelurahan?->id,
            'title'          => 'Meja Belajar Minimalis Kayu Pinus',
            'description'    => 'Meja kokoh, ada 2 laci penyimpanan.',
            'price'          => 250000,
            'condition'      => 'baik',
            'status'         => Listing::DIPESAN,
            'is_negotiable'  => false,
            'published_at'   => now()->subDays(3),
        ]);

        DemoListingPhotos::attach($listingDiserahkan, 'meja-belajar');

        $orderDiserahkan = Order::firstOrCreate(['order_number' => 'RL-20261019-0089'], [
            'order_number'        => 'RL-20261019-0089',
            'listing_id'          => $listingDiserahkan->id,
            'buyer_id'            => $buyer->id,
            'seller_id'           => $seller->id,
            'agreed_price'        => 250000,
            'delivery_method'     => Order::DELIVERY_COD,
            'delivery_address'    => 'COD di Sukamaju',
            'status'              => Order::STATUS_DISERAHKAN,
            'seller_confirmed_at' => now()->subHours(12),
            'auto_complete_at'    => now()->addDays(3)->subHours(12),
            'created_at'          => now()->subDay(),
        ]);

        Payment::firstOrCreate(['order_id' => $orderDiserahkan->id], [
            'order_id'     => $orderDiserahkan->id,
            'amount'       => 250000,
            'platform_fee' => 7500, // 3% dari 250.000
            'method'       => Payment::METHOD_QRIS,
            'status'       => Payment::STATUS_BERHASIL,
            'paid_at'      => now()->subHours(20),
        ]);
    }
}
