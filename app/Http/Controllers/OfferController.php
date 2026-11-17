<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 6.2.2 — Backend tawar harga: ajukan, terima, tolak, kedaluwarsa.
 *
 * Kriteria penerimaan (PPLTIMB-63):
 * - Pembeli mengajukan tawaran dari detail barang atau chat
 * - Penjual menerima atau menolak
 * - Tawaran tanpa respons menjadi kedaluwarsa (batas 24 jam)
 * - Tawaran yang diterima menjadi harga checkout
 */
class OfferController extends Controller
{
    /**
     * 6.2.2.1 — Pembeli mengajukan tawaran harga dalam suatu percakapan.
     */
    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $userId = Auth::id();

        // Hanya pembeli dalam percakapan ini yang dapat mengajukan tawaran
        if ((int) $conversation->buyer_id !== (int) $userId) {
            abort(403, 'Hanya pembeli yang dapat mengajukan tawaran.');
        }

        // Barang harus masih aktif dan negotiable
        $listing = $conversation->listing;
        if (! $listing || $listing->status !== \App\Models\Listing::AKTIF) {
            return back()->with('error', 'Barang ini sudah tidak tersedia untuk ditawar.');
        }
        if (! $listing->is_negotiable) {
            return back()->with('error', 'Barang ini tidak dapat ditawar.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1000', 'max:999999999'],
        ], [
            'amount.required' => 'Masukkan harga tawaran.',
            'amount.numeric'  => 'Harga tawaran harus berupa angka.',
            'amount.min'      => 'Harga tawaran minimal Rp 1.000.',
            'amount.max'      => 'Harga tawaran terlalu besar.',
        ]);

        // Batalkan semua tawaran sebelumnya yang masih 'menunggu' di percakapan ini
        $conversation->offers()
            ->where('status', Offer::MENUNGGU)
            ->update(['status' => Offer::DIBATALKAN]);

        // Buat tawaran baru dengan masa berlaku 24 jam
        $conversation->offers()->create([
            'offerer_id' => $userId,
            'amount'     => (int) $validated['amount'],
            'status'     => Offer::MENUNGGU,
            'expires_at' => now()->addHours(24),
        ]);

        $conversation->touch();

        return redirect()->route('pesan.show', $conversation)
            ->with('success', 'Tawaran berhasil dikirim.');
    }

    /**
     * 6.2.2.2 — Penjual menerima atau menolak tawaran.
     */
    public function respond(Request $request, Conversation $conversation, Offer $offer): RedirectResponse
    {
        $userId = Auth::id();

        // Hanya penjual dalam percakapan ini yang boleh merespons
        if ((int) $conversation->seller_id !== (int) $userId) {
            abort(403, 'Hanya penjual yang dapat merespons tawaran.');
        }

        // Tawaran harus milik percakapan ini
        if ((int) $offer->conversation_id !== (int) $conversation->id) {
            abort(404);
        }

        // Tawaran harus masih 'menunggu'
        if ($offer->status !== Offer::MENUNGGU) {
            return back()->with('error', 'Tawaran ini sudah tidak aktif.');
        }

        // Periksa kedaluwarsa
        if ($offer->expires_at && $offer->expires_at->isPast()) {
            $offer->update(['status' => Offer::KEDALUWARSA]);
            return back()->with('error', 'Tawaran sudah kedaluwarsa.');
        }

        $validated = $request->validate([
            'action' => ['required', 'in:diterima,ditolak'],
        ]);

        $offer->update(['status' => $validated['action']]);

        $pesan = $validated['action'] === Offer::DITERIMA
            ? 'Tawaran diterima! Pembeli sekarang dapat melanjutkan ke pembayaran.'
            : 'Tawaran ditolak.';

        $conversation->touch();

        return redirect()->route('pesan.show', $conversation)
            ->with('success', $pesan);
    }
}

