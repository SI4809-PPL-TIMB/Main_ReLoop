<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * Tampilkan daftar percakapan pengguna (sebagai pembeli atau penjual).
     */
    public function index(): View
    {
        $userId = Auth::id();

        $conversations = Conversation::query()
            ->where(function ($query) use ($userId) {
                $query->where('buyer_id', $userId)
                    ->orWhere('seller_id', $userId);
            })
            ->with([
                'listing.primaryPhoto',
                'buyer.profile',
                'seller.profile',
                'latestMessage.sender',
            ])
            ->withCount([
                'messages as unread_count' => function ($query) use ($userId) {
                    $query->where('sender_id', '!=', $userId)
                        ->whereNull('read_at');
                },
            ])
            ->latest('updated_at')
            ->get();

        return view('pesan.index', compact('conversations'));
    }

    /**
     * Buka atau buat percakapan baru untuk barang tertentu.
     * Aturan: Pembeli tidak bisa mengirim chat ke barang miliknya sendiri.
     */
    public function open(Listing $listing): RedirectResponse
    {
        $userId = Auth::id();

        // Kriteria penerimaan: Tidak bisa chat ke barang sendiri
        if ((int) $listing->user_id === (int) $userId) {
            return redirect()->route('barang.detail', $listing)
                ->with('error', 'Tidak bisa mengirim pesan ke barang milik sendiri.');
        }

        // Ambil percakapan yang sudah ada atau buat baru per (listing, buyer)
        $conversation = Conversation::firstOrCreate(
            [
                'listing_id' => $listing->id,
                'buyer_id'   => $userId,
            ],
            [
                'seller_id'  => $listing->user_id,
            ]
        );

        return redirect()->route('pesan.show', $conversation);
    }

    /**
     * Tampilkan detail ruang obrolan per barang.
     */
    public function show(Conversation $conversation): View
    {
        $userId = Auth::id();

        // Otorisasi: Hanya pembeli atau penjual yang terlibat yang boleh melihat percakapan
        if ((int) $conversation->buyer_id !== (int) $userId && (int) $conversation->seller_id !== (int) $userId) {
            abort(403, 'Anda tidak memiliki akses ke percakapan ini.');
        }

        // Tandai pesan dari lawan bicara sebagai telah dibaca
        $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->load([
            'listing.primaryPhoto',
            'buyer.profile',
            'seller.profile',
            'messages.sender.profile',
        ]);

        return view('pesan.show', compact('conversation'));
    }

    /**
     * Kirim pesan baru dalam percakapan.
     */
    public function storeMessage(Request $request, Conversation $conversation): RedirectResponse
    {
        $userId = Auth::id();

        // Otorisasi
        if ((int) $conversation->buyer_id !== (int) $userId && (int) $conversation->seller_id !== (int) $userId) {
            abort(403, 'Anda tidak memiliki akses ke percakapan ini.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'body.required' => 'Pesan tidak boleh kosong.',
            'body.max'      => 'Pesan maksimal 2000 karakter.',
        ]);

        $conversation->messages()->create([
            'sender_id' => $userId,
            'body'      => trim($validated['body']),
        ]);

        // Perbarui timestamp percakapan agar naik ke daftar teratas
        $conversation->touch();

        return redirect()->route('pesan.show', $conversation);
    }
}

