<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCode extends Notification
{
    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode atur ulang kata sandi ReLoop')
            ->greeting('Halo!')
            ->line('Kode verifikasi untuk mengatur ulang kata sandimu:')
            ->line($this->code)
            ->line('Kode berlaku 10 menit. Jangan bagikan kode ini kepada siapa pun.')
            ->line('Jika kamu tidak meminta perubahan ini, abaikan email ini.');
    }
}
