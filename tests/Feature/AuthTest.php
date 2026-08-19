<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    public function test_user_can_login_with_phone_and_password(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, attributes: ['phone' => '081234567890']);

        $response = $this->post('/login', [
            'identifier' => '081234567890',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_email_and_password(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, attributes: ['email' => 'admin@test.local']);

        $response = $this->post('/login', [
            'identifier' => 'admin@test.local',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $masjid = $this->createMasjid();
        $this->createUser($masjid, attributes: ['phone' => '081234567890']);

        $response = $this->from('/login')->post('/login', [
            'identifier' => '081234567890',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $masjid = $this->createMasjid();
        $this->createUser($masjid, attributes: ['phone' => '081234567890', 'is_active' => false]);

        $response = $this->post('/login', [
            'identifier' => '081234567890',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->createMasjid();

        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('login'));
    }
}
