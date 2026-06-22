<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_switch_to_a_supported_locale(): void
    {
        $response = $this->from('/')->post(route('locale.update', 'fa'));

        $response->assertRedirect('/');
        $response->assertCookie('locale', 'fa');
    }

    public function test_unsupported_locale_returns_not_found(): void
    {
        $response = $this->post(route('locale.update', 'de'));

        $response->assertNotFound();
    }

    public function test_locale_cookie_is_applied_to_the_application(): void
    {
        $response = $this->withCookie('locale', 'ps')->get('/');

        $response->assertOk();
        $this->assertSame('ps', app()->getLocale());
    }
}
