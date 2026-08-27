<?php

use App\Http\Controllers\Central\PlatformAdminController;
use App\Http\Controllers\Central\RegistrationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// === HALAMAN PUSAT SAAS (domain root, config('tenancy.central_domain')) ===
Route::get('/', fn () => Inertia::render('Central/Landing'))->name('central.home');

Route::get('/daftar', [RegistrationController::class, 'showForm'])->name('central.register');
Route::middleware('throttle:6,1')->post('/daftar', [RegistrationController::class, 'store'])->name('central.register.store');

// === SUPERADMIN PLATFORM (kelola semua tenant, hanya bisa diakses dari domain pusat) ===
Route::prefix('platform-admin')->name('platform-admin.')->group(function () {
    Route::get('/login', [PlatformAdminController::class, 'showLogin'])->name('login');
    Route::middleware('throttle:6,1')->post('/login', [PlatformAdminController::class, 'login']);

    Route::middleware('auth.platform')->group(function () {
        Route::post('/logout', [PlatformAdminController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [PlatformAdminController::class, 'dashboard'])->name('dashboard');
        Route::post('/tenant/{tenant}/toggle-active', [PlatformAdminController::class, 'toggleActive'])->name('tenant.toggle-active');

        Route::get('/tenant/{tenant}', [PlatformAdminController::class, 'showTenant'])->name('tenant.show');
        Route::put('/tenant/{tenant}/fee', [PlatformAdminController::class, 'updateFee'])->name('tenant.fee');
        Route::put('/tenant/{tenant}/active-until', [PlatformAdminController::class, 'updateActiveUntil'])->name('tenant.active-until');
        Route::post('/tenant/{tenant}/payments', [PlatformAdminController::class, 'storePayment'])->name('tenant.payments.store');
        Route::delete('/tenant/{tenant}/payments/{payment}', [PlatformAdminController::class, 'destroyPayment'])->name('tenant.payments.destroy');

        Route::get('/pendapatan', [PlatformAdminController::class, 'revenue'])->name('revenue');
        Route::put('/pengaturan/tarif', [PlatformAdminController::class, 'updateDefaultFee'])->name('settings.fee');
    });
});

// === REDIRECT SEMENTARA UNTUK LINK/QR LAMA (dari sebelum root domain jadi hub SaaS) ===
// Khusus tenant pertama yang dulunya live langsung di root domain — QR code fisik
// (label aset, presensi kegiatan) dan link WA yang sudah terlanjur beredar masih
// mengarah ke root domain, tidak bisa ditarik lagi. Lihat docs/multi-tenancy-limitations.md.
if ($legacySlug = config('tenancy.legacy_root_redirect_slug')) {
    $legacyHost = "https://{$legacySlug}.".config('tenancy.central_domain');

    Route::get('/aset/{assetCode}', fn ($assetCode) => redirect()->away("{$legacyHost}/aset/{$assetCode}"))
        ->where('assetCode', '.*');

    Route::get('/hadir/{activity}/{token}', fn ($activity, $token) => redirect()->away("{$legacyHost}/hadir/{$activity}/{$token}"));
}
