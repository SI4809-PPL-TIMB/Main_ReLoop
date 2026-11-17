<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Tests\TestCase;

class EmailKodeTest extends TestCase
{
    public function test_template_email_kode_menampilkan_kode_dan_masa_berlaku(): void
    {
        $html = view('emails.kode', [
            'judul' => 'Verifikasi email kamu',
            'pembuka' => 'Halo Budi',
            'kode' => '48213',
            'berlaku' => '5 menit',
        ])->render();

        $this->assertStringContainsString('48213', $html);
        $this->assertStringContainsString('5 menit', $html);
        $this->assertStringContainsString('Halo Budi', $html);
    }

    public function test_email_lupa_sandi_memakai_template_kode(): void
    {
        $html = (string) (new PasswordResetCode('654321'))->toMail(new User)->render();

        $this->assertStringContainsString('654321', $html);
        $this->assertStringContainsString('10 menit', $html);
        $this->assertStringContainsString('Atur ulang kata sandi', $html);
    }

    public function test_email_kode_menyertakan_logo_reloop_inline(): void
    {
        \Illuminate\Support\Facades\Mail::send(
            ['html' => 'emails.kode'],
            ['judul' => 'Tes', 'pembuka' => 'Halo', 'kode' => '11111', 'berlaku' => '5 menit'],
            fn ($m) => $m->to('a@b.test')->subject('Tes')
        );

        $raw = (string) app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage()->toString();
        $this->assertStringContainsString('cid:', $raw);
        $this->assertStringContainsString('reloop-logo.png', $raw);
    }
}
