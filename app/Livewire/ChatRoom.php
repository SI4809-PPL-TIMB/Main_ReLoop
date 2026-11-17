<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\Offer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * 6.1 + 6.2 — Komponen chat dengan dukungan tawaran harga.
 */
class ChatRoom extends Component
{
    public Conversation $conversation;
    public string $body = '';

    // 6.2 — form tawaran
    public bool  $showTawarForm = false;
    public string $tawarAmount  = '';

    public function mount(Conversation $conversation): void
    {
        $userId = Auth::id();
        if ((int) $conversation->buyer_id !== (int) $userId && (int) $conversation->seller_id !== (int) $userId) {
            abort(403, 'Anda tidak memiliki akses ke percakapan ini.');
        }

        $this->conversation = $conversation;
        $this->markAsRead();
    }

    public function markAsRead(): void
    {
        $userId = Auth::id();
        $this->conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function sendMessage(): void
    {
        $userId = Auth::id();
        if ((int) $this->conversation->buyer_id !== (int) $userId && (int) $this->conversation->seller_id !== (int) $userId) {
            abort(403);
        }

        $this->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'body.required' => 'Pesan tidak boleh kosong.',
            'body.max'      => 'Pesan maksimal 2000 karakter.',
        ]);

        $text = trim($this->body);
        if ($text === '') {
            return;
        }

        $this->conversation->messages()->create([
            'sender_id' => $userId,
            'body'      => $text,
        ]);

        $this->conversation->touch();
        $this->reset('body');

        $this->dispatch('message-sent');
    }

    /** 6.2 — Pembeli membuka / menutup form tawaran harga. */
    public function toggleTawarForm(): void
    {
        $this->showTawarForm = ! $this->showTawarForm;
        $this->reset('tawarAmount');
        $this->resetValidation('tawarAmount');
    }

    /** 6.2 — Pembeli mengajukan tawaran langsung dari Livewire. */
    public function ajukanTawar(): void
    {
        $userId = Auth::id();

        // Hanya pembeli
        if ((int) $this->conversation->buyer_id !== (int) $userId) {
            abort(403, 'Hanya pembeli yang dapat mengajukan tawaran.');
        }

        $listing = $this->conversation->listing;
        if (! $listing || $listing->status !== \App\Models\Listing::AKTIF) {
            $this->addError('tawarAmount', 'Barang ini sudah tidak tersedia untuk ditawar.');
            return;
        }
        if (! $listing->is_negotiable) {
            $this->addError('tawarAmount', 'Barang ini tidak dapat ditawar.');
            return;
        }

        // Bersihkan format rupiah (pisahkan titik ribuan)
        $rawAmount = preg_replace('/[^\d]/', '', $this->tawarAmount);

        $this->validate([
            'tawarAmount' => ['required'],
        ], [
            'tawarAmount.required' => 'Masukkan harga tawaran.',
        ]);

        $amount = (int) $rawAmount;
        if ($amount < 1000) {
            $this->addError('tawarAmount', 'Harga tawaran minimal Rp 1.000.');
            return;
        }
        if ($amount > 999999999) {
            $this->addError('tawarAmount', 'Harga tawaran terlalu besar.');
            return;
        }

        // Batalkan tawaran lama yang masih menunggu
        $this->conversation->offers()
            ->where('status', Offer::MENUNGGU)
            ->update(['status' => Offer::DIBATALKAN]);

        $this->conversation->offers()->create([
            'offerer_id' => $userId,
            'amount'     => $amount,
            'status'     => Offer::MENUNGGU,
            'expires_at' => now()->addHours(24),
        ]);

        $this->conversation->touch();
        $this->showTawarForm = false;
        $this->reset('tawarAmount');

        $this->dispatch('message-sent');
    }

    /** 6.2 — Penjual menerima tawaran. */
    public function terimaTawar(int $offerId): void
    {
        $this->respondTawar($offerId, Offer::DITERIMA);
    }

    /** 6.2 — Penjual menolak tawaran. */
    public function tolakTawar(int $offerId): void
    {
        $this->respondTawar($offerId, Offer::DITOLAK);
    }

    private function respondTawar(int $offerId, string $status): void
    {
        $userId = Auth::id();

        if ((int) $this->conversation->seller_id !== (int) $userId) {
            abort(403, 'Hanya penjual yang dapat merespons tawaran.');
        }

        $offer = $this->conversation->offers()->findOrFail($offerId);

        if ($offer->status !== Offer::MENUNGGU) {
            return; // sudah diproses
        }

        if ($offer->expires_at && $offer->expires_at->isPast()) {
            $offer->update(['status' => Offer::KEDALUWARSA]);
            return;
        }

        $offer->update(['status' => $status]);
        $this->conversation->touch();

        $this->dispatch('message-sent');
    }

    public function render(): View
    {
        // Setiap polling siklus, tandai pesan yang masuk sebagai terbaca
        $this->markAsRead();

        $userId   = Auth::id();
        $isPembeli = (int) $this->conversation->buyer_id === (int) $userId;

        $messages = $this->conversation->messages()
            ->with('sender.profile')
            ->get();

        // Muat semua tawaran percakapan ini, diurutkan dari terbaru
        $offers = $this->conversation->offers()
            ->with('offerer')
            ->latest()
            ->get();

        // Tawaran aktif (menunggu + belum kedaluwarsa)
        $activeoffer = $offers->first(fn ($o) =>
            $o->status === Offer::MENUNGGU
            && (! $o->expires_at || $o->expires_at->isFuture())
        );

        // Tawaran terakhir yang diterima (untuk harga checkout)
        $acceptedOffer = $offers->first(fn ($o) => $o->status === Offer::DITERIMA);

        $listing = $this->conversation->listing;

        return view('livewire.chat-room', [
            'listing'       => $listing,
            'other'         => $this->conversation->otherUser(),
            'messages'      => $messages,
            'offers'        => $offers,
            'activeOffer'   => $activeoffer,
            'acceptedOffer' => $acceptedOffer,
            'isPembeli'     => $isPembeli,
        ]);
    }
}
