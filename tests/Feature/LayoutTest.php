<?php

namespace Tests\Feature;

use Illuminate\Support\HtmlString;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    public function test_home_page_returns_a_successful_response(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_layout_shows_bottom_nav_with_active_tab(): void
    {
        $html = view('layouts.app', [
            'slot' => new HtmlString('isi'),
            'nav' => 'chat',
        ])->render();

        $this->assertStringContainsString('id="bottom-nav"', $html);
        $this->assertStringContainsString('data-active="chat"', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
        $this->assertStringContainsString('pb-24', $html);
    }

    public function test_layout_hides_bottom_nav_when_nav_is_not_set(): void
    {
        $html = view('layouts.app', ['slot' => new HtmlString('isi')])->render();

        $this->assertStringNotContainsString('id="bottom-nav"', $html);
    }

    public function test_top_bar_renders_title_and_back_button(): void
    {
        $html = view('components.top-bar', ['title' => 'Notifikasi', 'back' => '/'])->render();

        $this->assertStringContainsString('Notifikasi', $html);
        $this->assertStringContainsString('aria-label="Kembali"', $html);
    }

    public function test_top_bar_has_no_back_button_without_back_prop(): void
    {
        $html = view('components.top-bar', ['title' => 'Aktivitas'])->render();

        $this->assertStringNotContainsString('aria-label="Kembali"', $html);
    }
}
