<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderSetorRequest;
use App\Models\MitraPengepul;
use App\Models\OrderSetor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 9.1.2 · Controller Order Setor Sampah Daur Ulang (PPLTIMB-170).
 */
class OrderSetorController extends Controller
{
    /**
     * Tampilkan form pembuatan order setor ke mitra pengepul tertentu.
     */
    public function create(Request $request, MitraPengepul $mitra): View
    {
        if ($mitra->status !== MitraPengepul::TERVERIFIKASI) {
            abort(404, 'Mitra belum terverifikasi');
        }

        $mitra->load(['kelurahan.kecamatan.kabupaten', 'area', 'harga.category', 'jam']);

        $user = $request->user();
        $kelurahan = $user->profile?->kelurahan;
        $kecamatanId = $kelurahan?->kecamatan_id;

        // Opsi jemput hanya jika mitra punya layanan jemput DAN alamat user masuk area layanan mitra
        $bisaJemput = $mitra->layanan_jemput && $kecamatanId && $mitra->area->contains('id', $kecamatanId);

        // Kategori sampah yang diterima mitra (beserta harga per kg)
        $daftarHarga = $mitra->harga->filter(fn ($h) => $h->harga_per_kg > 0);

        if ($daftarHarga->isEmpty()) {
            abort(404, 'Mitra belum mengatur harga untuk kategori daur ulang');
        }

        // Pilihan awal jenis sampah (bisa lewat query ?kategori=id atau bawaan pertama)
        $kategoriPilihan = $request->query('kategori')
            ? $daftarHarga->firstWhere('category_id', (int) $request->query('kategori'))
            : $daftarHarga->first();

        return view('setor.create', [
            'mitra'           => $mitra,
            'daftarHarga'     => $daftarHarga,
            'kategoriPilihan' => $kategoriPilihan ?? $daftarHarga->first(),
            'bisaJemput'      => $bisaJemput,
            'kelurahan'       => $kelurahan,
            'patokan'         => $user->profile?->patokan,
        ]);
    }

    /**
     * Simpan order setoran ke database dengan status awal 'menunggu' dan harga per kg terkunci.
     */
    public function store(OrderSetorRequest $request, MitraPengepul $mitra): RedirectResponse
    {
        if ($mitra->status !== MitraPengepul::TERVERIFIKASI) {
            abort(404, 'Mitra belum terverifikasi');
        }

        // Kunci harga per kg dari mitra_harga saat ini
        $mitraHarga = $mitra->harga()->where('category_id', $request->category_id)->firstOrFail();
        $hargaTerkunci = $mitraHarga->harga_per_kg;

        $perkiraanBerat = (float) $request->perkiraan_berat;
        $totalEstimasi = (int) round($perkiraanBerat * $hargaTerkunci);

        $user = $request->user();
        $kelurahan = $user->profile?->kelurahan;
        $alamatJemput = $request->metode === OrderSetor::METODE_JEMPUT
            ? ($kelurahan ? $kelurahan->breadcrumb() : null)
            : null;

        $order = OrderSetor::create([
            'order_number'    => OrderSetor::generateOrderNumber(),
            'user_id'         => $user->id,
            'mitra_id'        => $mitra->id,
            'category_id'     => (int) $request->category_id,
            'perkiraan_berat' => $perkiraanBerat,
            'harga_per_kg'    => $hargaTerkunci,
            'total_estimasi'  => $totalEstimasi,
            'metode'          => $request->metode,
            'alamat_jemput'   => $alamatJemput,
            'patokan'         => $request->patokan ?: $user->profile?->patokan,
            'catatan'         => $request->catatan,
            'status'          => OrderSetor::STATUS_MENUNGGU,
        ]);

        return redirect()
            ->route('setor.show', $order->order_number)
            ->with('status', 'Order setoran berhasil dibuat! Pengepul akan segera meninjau pesananmu.');
    }

    /**
     * Halaman detail dan pelacakan status order setoran.
     */
    public function show(Request $request, string $orderNumber): View
    {
        $order = OrderSetor::with([
            'mitra.kelurahan.kecamatan.kabupaten',
            'mitra.user',
            'category',
            'user.profile.kelurahan.kecamatan.kabupaten',
        ])->where('order_number', $orderNumber)->firstOrFail();

        $user = $request->user();

        // Otorisasi: hanya customer pemilik, mitra yang bersangkutan, atau admin
        if (! $order->isCustomer($user) && ! $order->isMitra($user) && ! $user->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses ke pesanan setoran ini.');
        }

        return view('setor.show', [
            'order' => $order,
        ]);
    }

    /**
     * Batalkan order setor (hanya jika masih berstatus menunggu).
     */
    public function cancel(Request $request, string $orderNumber): RedirectResponse
    {
        $order = OrderSetor::where('order_number', $orderNumber)->firstOrFail();
        $user = $request->user();

        if (! $order->isCustomer($user)) {
            abort(403, 'Hanya pembuat order yang dapat membatalkan pesanan ini.');
        }

        if (! $order->canBeCancelled()) {
            return back()->with('error', 'Pesanan setoran sudah diproses dan tidak dapat dibatalkan.');
        }

        $order->update([
            'status'        => OrderSetor::STATUS_DIBATALKAN,
            'cancelled_at'  => now(),
            'cancel_reason' => $request->input('alasan', 'Dibatalkan oleh customer.'),
        ]);

        return redirect()
            ->route('setor.show', $order->order_number)
            ->with('status', 'Order setoran telah dibatalkan.');
    }
}
