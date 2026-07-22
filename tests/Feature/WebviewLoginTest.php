<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class WebviewLoginTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    public function test_valid_webview_token_logs_user_in_and_redirects(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'ustadz', ['phone' => '081234599001']);

        $token = $user->createToken('webview', ['webview'], now()->addMinutes(15))->plainTextToken;

        $response = $this->get("/webview-login?token={$token}&redirect=/admin/dashboard");

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_webview_token_is_rejected(): void
    {
        $response = $this->get('/webview-login?token=bogus-token');

        $response->assertStatus(401);
        $this->assertGuest();
    }

    public function test_webview_token_is_single_use(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'ustadz', ['phone' => '081234599002']);

        $token = $user->createToken('webview', ['webview'], now()->addMinutes(15))->plainTextToken;

        $this->get("/webview-login?token={$token}")->assertRedirect();

        auth()->logout();

        $response = $this->get("/webview-login?token={$token}");
        $response->assertStatus(401);
    }

    public function test_token_without_webview_ability_is_rejected(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'ustadz', ['phone' => '081234599003']);

        $token = $user->createToken('mobile-app', ['mobile'])->plainTextToken;

        $response = $this->get("/webview-login?token={$token}");

        $response->assertStatus(401);
        $this->assertGuest();
    }
}
