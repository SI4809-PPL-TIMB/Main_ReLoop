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
    public function __construct(public readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Atur Ulang Kata Sandi ReLoop')
            ->view('emails.kode', [
                'judul'   => 'Atur ulang kata sandi',
                'pembuka' => 'Kami menerima permintaan untuk mengatur ulang kata sandi akunmu. Gunakan kode berikut.',
                'kode'    => $this->code,
                'berlaku' => '10 menit',
            ]);
    }
}
