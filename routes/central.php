<?php

use App\Http\Controllers\Central\RegistrationController;
use App\Http\Controllers\Internal\DomainCheckController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// === HALAMAN PUSAT SAAS (domain root, config('tenancy.central_domain')) ===
Route::get('/', fn () => Inertia::render('Central/Landing'))->name('central.home');

Route::get('/daftar', [RegistrationController::class, 'showForm'])->name('central.register');
Route::middleware('throttle:6,1')->post('/daftar', [RegistrationController::class, 'store'])->name('central.register.store');

// === CADDY ON-DEMAND TLS "ASK" ENDPOINT ===
Route::middleware('throttle:30,1')->get('/internal/domain-check', [DomainCheckController::class, 'ask']);

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
