<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class SettingLogoTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    public function test_admin_can_upload_masjid_logo(): void
    {
        Storage::fake('public');

        $masjid = $this->createMasjid();
        $admin = $this->createUser($masjid, 'admin');

        $response = $this->actingAs($admin)->post(route('admin.settings.masjid.logo'), [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ]);

        $response->assertRedirect();
        $masjid->refresh();
        $this->assertNotNull($masjid->logo);
        Storage::disk('public')->assertExists($masjid->logo);
    }

    public function test_uploading_new_logo_deletes_the_old_one(): void
    {
        Storage::fake('public');

        $masjid = $this->createMasjid();
        $admin = $this->createUser($masjid, 'admin');

        $this->actingAs($admin)->post(route('admin.settings.masjid.logo'), [
            'logo' => UploadedFile::fake()->image('first.png'),
        ]);
        $firstPath = $masjid->refresh()->logo;

        $this->actingAs($admin)->post(route('admin.settings.masjid.logo'), [
            'logo' => UploadedFile::fake()->image('second.png'),
        ]);

        Storage::disk('public')->assertMissing($firstPath);
        $this->assertNotSame($firstPath, $masjid->refresh()->logo);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');

        $masjid = $this->createMasjid();
        $admin = $this->createUser($masjid, 'admin');

        $response = $this->actingAs($admin)->post(route('admin.settings.masjid.logo'), [
            'logo' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertSessionHasErrors('logo');
        $this->assertNull($masjid->refresh()->logo);
    }

    public function test_admin_can_remove_masjid_logo(): void
    {
        Storage::fake('public');

        $masjid = $this->createMasjid();
        $admin = $this->createUser($masjid, 'admin');

        $this->actingAs($admin)->post(route('admin.settings.masjid.logo'), [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);
        $path = $masjid->refresh()->logo;

        $response = $this->actingAs($admin)->delete(route('admin.settings.masjid.logo.destroy'));

        $response->assertRedirect();
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($masjid->refresh()->logo);
    }
}
