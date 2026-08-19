<?php

use App\Http\Controllers\Api\Mobile\MobileAuthController;
use App\Http\Controllers\Api\Mobile\MobileCapaianController;
use App\Http\Controllers\Api\Mobile\MobileDashboardController;
use App\Http\Controllers\Api\Mobile\MobileNotificationController;
use App\Http\Controllers\Api\Mobile\MobilePresensiController;
use App\Http\Controllers\Api\Mobile\MobileSppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// === MOBILE API v1 (Flutter app Ustadz/Ustadzah) ===
Route::prefix('mobile/v1')->name('mobile.')->group(function () {

    Route::post('login', [MobileAuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [MobileAuthController::class, 'logout'])->name('logout');
        Route::post('fcm-token', [MobileAuthController::class, 'updateFcmToken'])->name('fcm-token');
        Route::get('profile', [MobileAuthController::class, 'profile'])->name('profile');
        Route::get('webview-token', [MobileAuthController::class, 'webviewToken'])->name('webview-token');

        Route::middleware('role:ustadz|admin|super_admin')->group(function () {

            Route::get('dashboard', [MobileDashboardController::class, 'index'])->name('dashboard');

            // Presensi
            Route::prefix('presensi')->name('presensi.')->group(function () {
                Route::get('kelas', [MobilePresensiController::class, 'kelasList'])->name('kelas');
                Route::get('kelas/{class}/santri', [MobilePresensiController::class, 'santriList'])->name('santri');
                Route::get('kelas/{class}/today', [MobilePresensiController::class, 'todayAttendance'])->name('today');
                Route::post('kelas/{class}/submit', [MobilePresensiController::class, 'submit'])->name('submit');
                Route::get('rekap/{class}', [MobilePresensiController::class, 'rekap'])->name('rekap');
            });

            // Capaian santri
            Route::prefix('capaian')->name('capaian.')->group(function () {
                Route::get('cari', [MobileCapaianController::class, 'searchStudents'])->name('cari');
                Route::get('santri/{student}/temukan', [MobileCapaianController::class, 'findStudent'])->name('temukan');
                Route::get('kelas/{class}/santri', [MobileCapaianController::class, 'santriList'])->name('santri');
                Route::get('santri/{student}', [MobileCapaianController::class, 'detail'])->name('detail');
                Route::get('santri/{student}/hafalan', [MobileCapaianController::class, 'hafalan'])->name('hafalan');
                Route::post('santri/{student}/nilai', [MobileCapaianController::class, 'inputNilai'])->name('nilai');
                Route::post('santri/{student}/hafalan', [MobileCapaianController::class, 'updateHafalan'])->name('hafalan.update');
                Route::get('santri/{student}/harian', [MobileCapaianController::class, 'dailyProgress'])->name('harian');
                Route::post('santri/{student}/harian', [MobileCapaianController::class, 'inputDailyProgress'])->name('harian.store');
            });

            // SPP (read-only untuk ustadz)
            Route::prefix('spp')->name('spp.')->group(function () {
                Route::get('kelas/{class}', [MobileSppController::class, 'kelasSpp'])->name('kelas');
                Route::get('santri/{student}', [MobileSppController::class, 'santriSpp'])->name('santri');
            });

            // Notifikasi
            Route::prefix('notifications')->name('notifications.')->group(function () {
                Route::get('/', [MobileNotificationController::class, 'index'])->name('index');
                Route::post('{id}/read', [MobileNotificationController::class, 'markRead'])->name('read');
                Route::post('read-all', [MobileNotificationController::class, 'markAllRead'])->name('read-all');
            });
        });
    });
});
