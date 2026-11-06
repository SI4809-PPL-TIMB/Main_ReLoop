<?php

namespace App\Livewire;

use App\Models\Conversation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChatRoom extends Component
{
    public Conversation $conversation;
    public string $body = '';

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

    public function render(): View
    {
        // Setiap polling siklus, tandai pesan yang masuk sebagai terbaca
        $this->markAsRead();

        $messages = $this->conversation->messages()
            ->with('sender.profile')
            ->get();

        return view('livewire.chat-room', [
            'listing'  => $this->conversation->listing,
            'other'    => $this->conversation->otherUser(),
            'messages' => $messages,
        ]);
    }
}

