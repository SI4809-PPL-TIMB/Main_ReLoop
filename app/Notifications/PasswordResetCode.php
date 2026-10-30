<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCode extends Notification
{
    use Queueable;

    /**
     * Kode OTP 6 digit untuk atur ulang kata sandi.
     */
    public function __construct(private readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Atur Ulang Kata Sandi ReLoop')
            ->greeting('Halo!')
            ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akunmu.')
            ->line('Gunakan kode verifikasi berikut:')
            ->line('**' . $this->code . '**')
            ->line('Kode ini berlaku selama **10 menit** dan hanya bisa dipakai sekali.')
            ->line('Jika kamu tidak merasa melakukan permintaan ini, abaikan saja email ini. Akunmu tetap aman.')
            ->salutation('Salam, Tim ReLoop');
    }
}
