<?php

namespace Tests\Feature;

use Tests\TestCase;

// PPLTIMB-135 · Syarat & Ketentuan dan Kebijakan Privasi sebagai halaman terpisah
class HalamanLegalTest extends TestCase
{
    public function test_syarat_ketentuan_bisa_dibuka_tanpa_login(): void
    {
        $this->get(route('syarat'))
            ->assertOk()
            ->assertSee('Syarat &amp; Ketentuan', false)
            ->assertSee('aria-label="Kembali"', false);
    }

    public function test_kebijakan_privasi_bisa_dibuka_tanpa_login(): void
    {
        $this->get(route('privasi'))
            ->assertOk()
            ->assertSee('Kebijakan Privasi')
            ->assertSee('aria-label="Kembali"', false);
    }

    public function test_tombol_kembali_mengarah_ke_halaman_sebelumnya(): void
    {
        $this->from(route('daftar'))
            ->get(route('syarat'))
            ->assertSee('href="' . route('daftar') . '"', false);
    }

    public function test_tombol_kembali_mengabaikan_referer_dari_luar(): void
    {
        $this->withHeader('referer', 'https://contoh-luar.test/jahat')
            ->get(route('privasi'))
            ->assertDontSee('contoh-luar.test')
            ->assertSee('href="' . url('/') . '"', false);
    }

    public function test_halaman_daftar_menautkan_ke_halaman_legal(): void
    {
        $this->get(route('daftar'))
            ->assertOk()
            ->assertSee('href="' . route('syarat') . '"', false)
            ->assertSee('href="' . route('privasi') . '"', false);
    }

    public function test_halaman_welcome_menautkan_ke_syarat_ketentuan(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="' . route('syarat') . '"', false);
    }
}

