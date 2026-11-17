<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\Offer;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChatRoom extends Component
{
    public Conversation $conversation;
    public string $body = '';
    public bool $showOffer = false;
    public string $offerAmount = '';

    public function mount(Conversation $conversation, bool $bukaTawar = false): void
    {
        $userId = Auth::id();
        if ((int) $conversation->buyer_id !== (int) $userId && (int) $conversation->seller_id !== (int) $userId) {
            abort(403, 'Anda tidak memiliki akses ke percakapan ini.');
        }

        $this->conversation = $conversation;
        $this->showOffer = $bukaTawar && (int) $conversation->buyer_id === (int) $userId;
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

    private function activeOffer(): ?Offer
    {
        return $this->conversation->offers()->where('status', 'menunggu')
            ->where('expires_at', '>', now())->latest('id')->first();
    }

    public function openOffer(): void
    {
        $this->resetErrorBag();
        $this->offerAmount = '';
        $this->showOffer = true;
    }

    public function closeOffer(): void
    {
        $this->showOffer = false;
    }

    /** Tawar (pembeli) atau tawar balik (jawab tawaran lawan). */
    public function submitOffer(): void
    {
        $uid = (int) Auth::id();
        $price = (int) $this->conversation->listing?->price;
        $isBuyer = (int) $this->conversation->buyer_id === $uid;
        $amount = (int) preg_replace('/\D/', '', $this->offerAmount);

        DB::transaction(function () use ($uid, $price, $isBuyer, $amount) {
            $c = Conversation::lockForUpdate()->find($this->conversation->id);
            $pending = $c->offers()->where('status', 'menunggu')->where('expires_at', '>', now())->latest('id')->first();

            if ($c->offers()->where('status', 'diterima')->exists()) {
                $this->addError('offerAmount', 'Tawaran sudah disepakati.');
                return;
            }
            if ($pending && (int) $pending->offerer_id === $uid) {
                $this->addError('offerAmount', 'Tunggu jawaban dulu, atau batalkan tawaranmu.');
                return;
            }
            if (! $pending && ! $isBuyer) {
                $this->addError('offerAmount', 'Penjual hanya bisa menawar balik.');
                return;
            }
            if ($amount < 1000 || $amount >= $price) {
                $this->addError('offerAmount', 'Tawaran harus di bawah harga barang (Rp ' . number_format($price, 0, ',', '.') . ').');
                return;
            }

            $pending?->update(['status' => 'ditolak']);
            $c->offers()->create([
                'offerer_id' => $uid,
                'amount'     => $amount,
                'expires_at' => now()->addHours(24),
            ]);
            $c->touch();
            $this->showOffer = false;
        });
        $this->dispatch('message-sent');
    }

    private function respond(int $id, string $status): void
    {
        $offer = $this->conversation->offers()->findOrFail($id);
        abort_if((int) $offer->offerer_id === (int) Auth::id(), 403);
        if ($offer->status === 'menunggu' && $offer->expires_at?->isFuture()) {
            $offer->update(['status' => $status]);
        }
    }

    public function acceptOffer(int $id): void { $this->respond($id, 'diterima'); }

    public function rejectOffer(int $id): void { $this->respond($id, 'ditolak'); }

    public function cancelOffer(int $id): void
    {
        $offer = $this->conversation->offers()->findOrFail($id);
        abort_if((int) $offer->offerer_id !== (int) Auth::id(), 403);
        if ($offer->status === 'menunggu') {
            $offer->update(['status' => 'dibatalkan']);
        }
    }

    public function render(): View
    {
        // Setiap polling siklus, tandai pesan yang masuk sebagai terbaca
        $this->markAsRead();

        $this->conversation->offers()->where('status', 'menunggu')
            ->where('expires_at', '<=', now())->update(['status' => 'dibatalkan']);

        $messages = $this->conversation->messages()
            ->with('sender.profile')
            ->get();
        $offers = $this->conversation->offers()->get();
        $timeline = $messages->concat($offers)->sortBy('created_at')->values();

        return view('livewire.chat-room', [
            'listing'  => $this->conversation->listing,
            'other'    => $this->conversation->otherUser(),
            'messages' => $messages,
            'timeline' => $timeline,
            'pending'  => $this->activeOffer(),
            'accepted' => $offers->firstWhere('status', 'diterima'),
        ]);
    }
}

