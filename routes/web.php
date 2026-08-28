<?php

use App\Http\Controllers\Activity\ActivityController;
use App\Http\Controllers\Activity\AttendanceController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Api\Mobile\WebviewLoginController;
use App\Http\Controllers\Asset\AssetController;
use App\Http\Controllers\Asset\AssetLoanController;
use App\Http\Controllers\Asset\MaintenanceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Finance\BudgetController;
use App\Http\Controllers\Finance\DonationController;
use App\Http\Controllers\Finance\FinanceReportController;
use App\Http\Controllers\Finance\KasAccountController;
use App\Http\Controllers\Finance\TransactionController;
use App\Http\Controllers\Finance\ZakatController;
use App\Http\Controllers\Finance\ZakatRecipientController;
use App\Http\Controllers\Jamaah\BroadcastController;
use App\Http\Controllers\Jamaah\JamaahController;
use App\Http\Controllers\Jamaah\SocialProgramController;
use App\Http\Controllers\Library\LibraryBookController;
use App\Http\Controllers\Library\LibraryLoanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Prayer\ImamController;
use App\Http\Controllers\Prayer\ImamScheduleController;
use App\Http\Controllers\Prayer\PrayerScheduleController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Public\PublicPortalController;
use App\Http\Controllers\Ramadhan\ItikafController;
use App\Http\Controllers\Ramadhan\KhatamTrackerController;
use App\Http\Controllers\Ramadhan\QurbanController;
use App\Http\Controllers\Ramadhan\RamadhanController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Settings\AuditLogController;
use App\Http\Controllers\Settings\DomainController;
use App\Http\Controllers\Settings\MasjidLocationController;
use App\Http\Controllers\Settings\SettingController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Staff\StaffAttendanceController;
use App\Http\Controllers\Study\MajelisAnggotaController;
use App\Http\Controllers\Study\MajelisController;
use App\Http\Controllers\Study\StudySessionController;
use App\Http\Controllers\Tpq\TpqAcademicYearController;
use App\Http\Controllers\Tpq\TpqAttendanceController;
use App\Http\Controllers\Tpq\TpqCertificateController;
use App\Http\Controllers\Tpq\TpqClassController;
use App\Http\Controllers\Tpq\TpqDailyProgressController;
use App\Http\Controllers\Tpq\TpqDashboardController;
use App\Http\Controllers\Tpq\TpqGradeController;
use App\Http\Controllers\Tpq\TpqHafalanController;
use App\Http\Controllers\Tpq\TpqReportCardController;
use App\Http\Controllers\Tpq\TpqSemesterController;
use App\Http\Controllers\Tpq\TpqSettingController;
use App\Http\Controllers\Tpq\TpqSppController;
use App\Http\Controllers\Tpq\TpqStudentController;
use App\Http\Controllers\Wakaf\BuildingProjectController;
use App\Http\Controllers\Wakaf\WakafController;
use App\Http\Controllers\WaliController;
use Illuminate\Support\Facades\Route;

// Satu-satunya tempat Route::domain() dipakai — aman di sini karena domain
// pusat memang fixed/diketahui saat boot, beda dengan subdomain/custom domain
// tenant yang jumlahnya dinamis (lihat ResolveTenant middleware). HARUS
// didaftarkan sebelum route tenant di bawah — Laravel mencocokkan route
// berdasarkan urutan pendaftaran, bukan spesifisitas constraint domain, jadi
// kalau ini didaftarkan belakangan, route "/" tanpa domain constraint di
// bawah akan selalu menang duluan untuk host manapun termasuk domain pusat.
Route::domain(config('tenancy.central_domain'))->group(base_path('routes/central.php'));

// === PUBLIC PORTAL ===
Route::get('/', [PublicPortalController::class, 'index'])->name('home');
Route::get('/donasi', [PublicPortalController::class, 'donation'])->name('public.donation');
Route::post('/donasi', [DonationController::class, 'publicStore'])->name('public.donation.store');
Route::get('/donasi/{donation}/status', [DonationController::class, 'publicStatus'])->name('public.donation.status');
Route::get('/laporan-keuangan', [PublicPortalController::class, 'financialReport'])->name('public.finance');
Route::get('/jadwal-imam', [PublicPortalController::class, 'imamSchedule'])->name('public.imam');
Route::get('/kegiatan', [PublicPortalController::class, 'activities'])->name('public.activities');
Route::get('/jam-digital', [PublicPortalController::class, 'digitalClock'])->name('public.clock');
// asset_code mengandung karakter "/" (format {kategori}/{tahun}/{urutan}), sehingga
// constraint diperlukan agar route menerima path bersegmen banyak.
Route::get('/aset/{assetCode}', [PublicPortalController::class, 'assetDetail'])
    ->where('assetCode', '.*')
    ->name('public.asset');

Route::get('/proyek-pembangunan/{project}', [PublicPortalController::class, 'buildingProject'])->name('public.wakaf-proyek');

Route::get('/daftar-kegiatan/{activity}', [ActivityController::class, 'publicRegister'])->name('public.activity.register');
Route::post('/daftar-kegiatan/{activity}', [ActivityController::class, 'publicRegisterStore'])->name('public.activity.register.store');
Route::get('/hadir/{activity}/{token}', [ActivityController::class, 'checkinForm'])->name('public.activity.checkin');
Route::post('/hadir/{activity}/{token}', [ActivityController::class, 'checkinStore'])->name('public.activity.checkin.store');

// === AUTH ===
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// === PORTAL WALI MURID (TPQ) ===
Route::prefix('wali')->name('wali.')->group(function () {
    Route::get('/login', [WaliController::class, 'showLogin'])->name('login');
    Route::post('/login', [WaliController::class, 'login']);

    Route::get('/lupa-password', [WaliController::class, 'showForgotPassword'])->name('forgot-password');
    Route::middleware('throttle:6,1')->group(function () {
        Route::post('/lupa-password/cari', [WaliController::class, 'findAccountForReset'])->name('forgot-password.find');
        Route::post('/lupa-password/kirim', [WaliController::class, 'sendResetLink'])->name('forgot-password.send');
    });
    Route::get('/reset-password/{token}', [WaliController::class, 'showResetPassword'])->name('reset-password.show');
    Route::post('/reset-password', [WaliController::class, 'resetPassword'])->name('reset-password.store');

    Route::middleware('auth.wali')->group(function () {
        Route::post('/logout', [WaliController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [WaliController::class, 'dashboard'])->name('dashboard');
        Route::get('/manifest.webmanifest', [WaliController::class, 'manifest'])->name('manifest');
        Route::get('/santri/{student}', [WaliController::class, 'studentDetail'])->name('santri');
        Route::post('/spp/{bill}/bukti', [WaliController::class, 'sppUploadProof'])->name('spp.proof.upload');
        Route::get('/raport/{reportCard}', [WaliController::class, 'reportCard'])->name('reportcard');
        Route::get('/raport/{reportCard}/pdf', [WaliController::class, 'reportCardPdf'])->name('reportcard.pdf');
        Route::post('/notifikasi', [WaliController::class, 'updateNotificationPreferences'])->name('notifications.update');
        Route::post('/push-subscribe', [WaliController::class, 'pushSubscribe'])->name('push.subscribe');
        Route::post('/push-unsubscribe', [WaliController::class, 'pushUnsubscribe'])->name('push.unsubscribe');
    });
});

// === WEBVIEW AUTO-LOGIN (Flutter app) ===
Route::get('/webview-login', [WebviewLoginController::class, 'login'])->name('webview.login');

// === PAYMENT WEBHOOK ===
Route::post('/webhook/midtrans', [PaymentController::class, 'midtransWebhook'])->name('webhook.midtrans');

// === ADMIN PANEL ===
// Note: README menggunakan middleware ['auth', 'verified'], namun skema users pada
// proyek ini login via nomor HP dan tidak memiliki email_verified_at, sehingga
// middleware 'verified' tidak diterapkan.
//
// Setiap grup fitur di bawah dipecah jadi sub-grup permission "view" (baca) dan
// "manage"/tindakan spesifik (tulis) — sebelum ini TIDAK ADA pengecekan permission
// di backend sama sekali (cuma sidebar Vue yang menyembunyikan menu), jadi siapa
// pun yang login bisa akses endpoint apa pun langsung lewat URL. Lihat
// database/seeders/RolePermissionSeeder.php untuk daftar lengkap permission per role.
Route::middleware(['auth'])->group(function () {

    // Profil akun sendiri — sengaja TANPA middleware permission, semua role
    // (termasuk ustadz) harus bisa lihat/ubah profilnya sendiri.
    Route::prefix('profil')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::post('avatar', [ProfileController::class, 'updateAvatar'])->name('avatar');
        Route::put('password', [ProfileController::class, 'updatePassword'])->name('password');
    });

    // Pengumuman (tidak di-prefix admin/ agar sesuai nama route README)
    Route::middleware('permission:announcement.view')->group(function () {
        Route::get('pengumuman', [AnnouncementController::class, 'index'])->name('pengumuman.index');
    });
    Route::middleware('permission:announcement.manage')->group(function () {
        Route::get('pengumuman/create', [AnnouncementController::class, 'create'])->name('pengumuman.create');
        Route::post('pengumuman', [AnnouncementController::class, 'store'])->name('pengumuman.store');
        Route::get('pengumuman/{pengumuman}/edit', [AnnouncementController::class, 'edit'])->name('pengumuman.edit');
        Route::put('pengumuman/{pengumuman}', [AnnouncementController::class, 'update'])->name('pengumuman.update');
        Route::delete('pengumuman/{pengumuman}', [AnnouncementController::class, 'destroy'])->name('pengumuman.destroy');
    });

    Route::prefix('admin')->name('admin.')->group(function () {

        // Dashboard tanpa middleware permission — semua role harus bisa akses,
        // isinya (admin penuh vs personal ustadz) dicabang di dalam controller
        // berdasarkan permission dashboard.admin.
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Keuangan — 4 tingkat permission yang sudah ada: view/create/approve/delete.
        // Tidak ada finance.edit tersendiri, jadi form edit + update dianggap
        // bagian dari finance.create (kemampuan "kelola" catatan keuangan).
        Route::prefix('keuangan')->name('finance.')->group(function () {
            Route::middleware('permission:finance.view')->group(function () {
                Route::get('transaksi', [TransactionController::class, 'index'])->name('transaksi.index');
                Route::get('kas', [KasAccountController::class, 'index'])->name('kas.index');
                Route::get('anggaran', [BudgetController::class, 'index'])->name('anggaran.index');
                Route::get('donasi', [DonationController::class, 'index'])->name('donasi.index');
                Route::get('donasi/{donasi}', [DonationController::class, 'show'])->name('donasi.show');
                Route::get('laporan', [FinanceReportController::class, 'index'])->name('laporan');
                Route::get('laporan/export-pdf', [FinanceReportController::class, 'exportPdf'])->name('laporan.export-pdf');
                Route::get('laporan/export-excel', [FinanceReportController::class, 'exportExcel'])->name('laporan.export-excel');
                Route::get('zakat', [ZakatController::class, 'index'])->name('zakat.index');
                Route::get('zakat/penerimaan/{penerimaan}', [ZakatController::class, 'show'])->name('zakat.penerimaan.show');
                Route::get('zakat/penerima', [ZakatRecipientController::class, 'index'])->name('zakat.penerima.index');
                Route::get('zakat/penerima/{penerima}', [ZakatRecipientController::class, 'show'])->name('zakat.penerima.show');
            });
            // transaksi.create/store SENGAJA tidak digerbang finance.create — controller
            // sudah punya logic bawaan "siapa saja bisa ajukan transaksi, status otomatis
            // pending kalau bukan approver" ($user->can('finance.approve') ? 'approved' :
            // 'pending'), jadi siapa pun yang login (bukan cuma pemegang izin finance) tetap
            // bisa mengajukan transaksi baru untuk disetujui bendahara/admin.
            Route::get('transaksi/create', [TransactionController::class, 'create'])->name('transaksi.create');
            Route::post('transaksi', [TransactionController::class, 'store'])->name('transaksi.store');

            Route::middleware('permission:finance.create')->group(function () {
                Route::get('transaksi/{transaksi}/edit', [TransactionController::class, 'edit'])->name('transaksi.edit');
                Route::put('transaksi/{transaksi}', [TransactionController::class, 'update'])->name('transaksi.update');
                Route::post('kas', [KasAccountController::class, 'store'])->name('kas.store');
                Route::put('kas/{kas}', [KasAccountController::class, 'update'])->name('kas.update');
                Route::get('anggaran/create', [BudgetController::class, 'create'])->name('anggaran.create');
                Route::post('anggaran', [BudgetController::class, 'store'])->name('anggaran.store');
                Route::get('anggaran/{anggaran}/edit', [BudgetController::class, 'edit'])->name('anggaran.edit');
                Route::put('anggaran/{anggaran}', [BudgetController::class, 'update'])->name('anggaran.update');
                Route::get('zakat/penerimaan/create', [ZakatController::class, 'create'])->name('zakat.penerimaan.create');
                Route::post('zakat/penerimaan', [ZakatController::class, 'store'])->name('zakat.penerimaan.store');
                Route::get('zakat/penerimaan/{penerimaan}/edit', [ZakatController::class, 'edit'])->name('zakat.penerimaan.edit');
                Route::put('zakat/penerimaan/{penerimaan}', [ZakatController::class, 'update'])->name('zakat.penerimaan.update');
                Route::get('zakat/penerima/create', [ZakatRecipientController::class, 'create'])->name('zakat.penerima.create');
                Route::post('zakat/penerima', [ZakatRecipientController::class, 'store'])->name('zakat.penerima.store');
                Route::get('zakat/penerima/{penerima}/edit', [ZakatRecipientController::class, 'edit'])->name('zakat.penerima.edit');
                Route::put('zakat/penerima/{penerima}', [ZakatRecipientController::class, 'update'])->name('zakat.penerima.update');
            });
            Route::middleware('permission:finance.approve')->group(function () {
                Route::post('transaksi/{transaksi}/approve', [TransactionController::class, 'approve'])->name('transaksi.approve');
            });
            Route::middleware('permission:finance.delete')->group(function () {
                Route::delete('transaksi/{transaksi}', [TransactionController::class, 'destroy'])->name('transaksi.destroy');
                Route::delete('kas/{kas}', [KasAccountController::class, 'destroy'])->name('kas.destroy');
                Route::delete('anggaran/{anggaran}', [BudgetController::class, 'destroy'])->name('anggaran.destroy');
                Route::delete('zakat/penerimaan/{penerimaan}', [ZakatController::class, 'destroy'])->name('zakat.penerimaan.destroy');
                Route::delete('zakat/penerima/{penerima}', [ZakatRecipientController::class, 'destroy'])->name('zakat.penerima.destroy');
            });
        });

        // Aset — view/create/edit/delete.
        Route::prefix('aset')->name('asset.')->group(function () {
            Route::middleware('permission:asset.view')->group(function () {
                Route::get('inventaris', [AssetController::class, 'index'])->name('inventaris.index');
                Route::get('inventaris/{inventaris}/qr', [AssetController::class, 'generateQr'])->name('inventaris.qr');
                Route::get('peminjaman', [AssetLoanController::class, 'index'])->name('peminjaman.index');
                Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
            });
            Route::middleware('permission:asset.create')->group(function () {
                Route::get('inventaris/create', [AssetController::class, 'create'])->name('inventaris.create');
                Route::post('inventaris', [AssetController::class, 'store'])->name('inventaris.store');
                Route::get('peminjaman/create', [AssetLoanController::class, 'create'])->name('peminjaman.create');
                Route::post('peminjaman', [AssetLoanController::class, 'store'])->name('peminjaman.store');
                Route::get('maintenance/create', [MaintenanceController::class, 'create'])->name('maintenance.create');
                Route::post('maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
            });
            Route::middleware('permission:asset.edit')->group(function () {
                Route::get('inventaris/{inventaris}/edit', [AssetController::class, 'edit'])->name('inventaris.edit');
                Route::put('inventaris/{inventaris}', [AssetController::class, 'update'])->name('inventaris.update');
                Route::post('peminjaman/{loan}/approve', [AssetLoanController::class, 'approve'])->name('peminjaman.approve');
                Route::post('peminjaman/{loan}/return', [AssetLoanController::class, 'returnAsset'])->name('peminjaman.return');
                Route::get('maintenance/{maintenance}/edit', [MaintenanceController::class, 'edit'])->name('maintenance.edit');
                Route::put('maintenance/{maintenance}', [MaintenanceController::class, 'update'])->name('maintenance.update');
            });
            Route::middleware('permission:asset.delete')->group(function () {
                Route::delete('inventaris/{inventaris}', [AssetController::class, 'destroy'])->name('inventaris.destroy');
                Route::delete('peminjaman/{loan}', [AssetLoanController::class, 'destroy'])->name('peminjaman.destroy');
                Route::delete('maintenance/{maintenance}', [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');
            });
        });

        // Kegiatan — view/create/edit (tidak ada activity.delete, destroy tetap
        // di bawah activity.edit, tidak berubah dari behavior yang ada).
        Route::prefix('kegiatan')->name('activity.')->group(function () {
            Route::middleware('permission:activity.view')->group(function () {
                Route::get('kalender', [ActivityController::class, 'calendar'])->name('calendar');
                Route::get('{activity}/presensi', [AttendanceController::class, 'show'])->name('attendance');
                Route::get('{activity}/presensi/export', [AttendanceController::class, 'exportPdf'])->name('attendance.export');
                Route::get('{kegiatan}/qr', [ActivityController::class, 'qrCode'])->name('qr');
            });
            Route::middleware('permission:activity.create')->group(function () {
                Route::get('tambah', [ActivityController::class, 'create'])->name('create');
                Route::post('/', [ActivityController::class, 'store'])->name('store');
            });
            Route::middleware('permission:activity.edit')->group(function () {
                Route::get('{kegiatan}/edit', [ActivityController::class, 'edit'])->name('edit');
                Route::put('{kegiatan}', [ActivityController::class, 'update'])->name('update');
                Route::delete('{kegiatan}', [ActivityController::class, 'destroy'])->name('destroy');
                Route::post('{activity}/presensi', [AttendanceController::class, 'store'])->name('attendance.store');
                Route::post('api/attendance-qr/{activity}', [AttendanceController::class, 'scanQr'])->name('attendance.scan');
            });
        });

        // Shalat & Imam — view/manage.
        Route::prefix('shalat')->name('prayer.')->group(function () {
            Route::middleware('permission:prayer.view')->group(function () {
                Route::get('jadwal', [PrayerScheduleController::class, 'index'])->name('schedule');
                Route::get('imam', [ImamController::class, 'index'])->name('imam.index');
                Route::get('jadwal-imam', [ImamScheduleController::class, 'index'])->name('imam-schedule');
                Route::get('jadwal-imam/export-pdf', [ImamScheduleController::class, 'exportPdf'])->name('imam-schedule.export');
            });
            Route::middleware('permission:prayer.manage')->group(function () {
                Route::post('jadwal/generate', [PrayerScheduleController::class, 'generate'])->name('schedule.generate');
                Route::get('imam/create', [ImamController::class, 'create'])->name('imam.create');
                Route::post('imam', [ImamController::class, 'store'])->name('imam.store');
                Route::get('imam/{imam}/edit', [ImamController::class, 'edit'])->name('imam.edit');
                Route::put('imam/{imam}', [ImamController::class, 'update'])->name('imam.update');
                Route::delete('imam/{imam}', [ImamController::class, 'destroy'])->name('imam.destroy');
                Route::post('jadwal-imam', [ImamScheduleController::class, 'store'])->name('imam-schedule.store');
                Route::post('jadwal-imam/{schedule}/ganti', [ImamScheduleController::class, 'substitute'])->name('imam-schedule.substitute');
                Route::post('jadwal-imam/notify', [ImamScheduleController::class, 'notifyAll'])->name('imam-schedule.notify');
            });
        });

        // Kajian & Majelis Taklim — view/manage.
        Route::prefix('kajian')->name('study.')->group(function () {
            Route::middleware('permission:study.view')->group(function () {
                Route::get('sesi', [StudySessionController::class, 'index'])->name('sesi.index');
                Route::get('majelis', [MajelisController::class, 'index'])->name('majelis.index');
                Route::get('majelis/{majelis}', [MajelisController::class, 'show'])->name('majelis.show');
            });
            Route::middleware('permission:study.manage')->group(function () {
                Route::get('sesi/create', [StudySessionController::class, 'create'])->name('sesi.create');
                Route::post('sesi', [StudySessionController::class, 'store'])->name('sesi.store');
                Route::get('sesi/{sesi}/edit', [StudySessionController::class, 'edit'])->name('sesi.edit');
                Route::put('sesi/{sesi}', [StudySessionController::class, 'update'])->name('sesi.update');
                Route::delete('sesi/{sesi}', [StudySessionController::class, 'destroy'])->name('sesi.destroy');
                Route::post('majelis', [MajelisController::class, 'store'])->name('majelis.store');
                Route::put('majelis/{majelis}', [MajelisController::class, 'update'])->name('majelis.update');
                Route::delete('majelis/{majelis}', [MajelisController::class, 'destroy'])->name('majelis.destroy');
                Route::post('majelis/{majelis}/anggota', [MajelisAnggotaController::class, 'store'])->name('majelis.anggota.store');
                Route::put('majelis/{majelis}/anggota/{anggota}', [MajelisAnggotaController::class, 'update'])->name('majelis.anggota.update');
                Route::delete('majelis/{majelis}/anggota/{anggota}', [MajelisAnggotaController::class, 'destroy'])->name('majelis.anggota.destroy');
            });
        });

        // TPQ
        Route::prefix('tpq')->name('tpq.')->group(function () {
            Route::middleware('permission:tpq.view')->group(function () {
                Route::get('/', [TpqDashboardController::class, 'index'])->name('dashboard');
                Route::get('santri', [TpqStudentController::class, 'index'])->name('santri.index');
                Route::get('santri/{student}/kartu', [TpqStudentController::class, 'card'])->name('santri.card');
                Route::get('absensi', [TpqAttendanceController::class, 'index'])->name('attendance.index');
                Route::get('absensi/{class}', [TpqAttendanceController::class, 'show'])->name('attendance.show');
                Route::get('absensi/{class}/rekap', [TpqAttendanceController::class, 'recap'])->name('attendance.recap');
                Route::get('absensi/{class}/rekap/export', [TpqAttendanceController::class, 'exportRecapPdf'])->name('attendance.recap.export');
                Route::get('hafalan/{student}', [TpqHafalanController::class, 'show'])->name('hafalan.show');
            });
            Route::middleware('permission:tpq.manage')->group(function () {
                Route::get('pengaturan', [TpqSettingController::class, 'edit'])->name('pengaturan.edit');
                Route::post('pengaturan', [TpqSettingController::class, 'update'])->name('pengaturan.update');
                Route::get('tahun-ajaran', [TpqAcademicYearController::class, 'index'])->name('tahun-ajaran.index');
                Route::post('tahun-ajaran', [TpqAcademicYearController::class, 'store'])->name('tahun-ajaran.store');
                Route::put('tahun-ajaran/{tahunAjaran}', [TpqAcademicYearController::class, 'update'])->name('tahun-ajaran.update');
                Route::delete('tahun-ajaran/{tahunAjaran}', [TpqAcademicYearController::class, 'destroy'])->name('tahun-ajaran.destroy');
                Route::get('semester', [TpqSemesterController::class, 'index'])->name('semester.index');
                Route::post('semester', [TpqSemesterController::class, 'store'])->name('semester.store');
                Route::put('semester/{semester}', [TpqSemesterController::class, 'update'])->name('semester.update');
                Route::delete('semester/{semester}', [TpqSemesterController::class, 'destroy'])->name('semester.destroy');
                Route::get('kelas', [TpqClassController::class, 'index'])->name('kelas.index');
                Route::post('kelas', [TpqClassController::class, 'store'])->name('kelas.store');
                Route::put('kelas/{kela}', [TpqClassController::class, 'update'])->name('kelas.update');
                Route::delete('kelas/{kela}', [TpqClassController::class, 'destroy'])->name('kelas.destroy');
                Route::get('santri/create', [TpqStudentController::class, 'create'])->name('santri.create');
                Route::post('santri', [TpqStudentController::class, 'store'])->name('santri.store');
                // Path statis WAJIB didaftarkan sebelum santri/{santri} di bawah — kalau
                // tidak, "santri/bulk-destroy" akan ketangkap duluan oleh {santri} (jadi
                // dianggap UUID "bulk-destroy" yang tidak valid, 404) karena keduanya
                // method DELETE dengan jumlah segmen sama.
                Route::delete('santri/bulk-destroy', [TpqStudentController::class, 'bulkDestroy'])->name('santri.bulk-destroy');
                Route::get('santri/{santri}/edit', [TpqStudentController::class, 'edit'])->name('santri.edit');
                Route::put('santri/{santri}', [TpqStudentController::class, 'update'])->name('santri.update');
                Route::delete('santri/{santri}', [TpqStudentController::class, 'destroy'])->name('santri.destroy');
                Route::get('santri/import/template', [TpqStudentController::class, 'importTemplate'])->name('santri.import-template');
                Route::post('santri/import', [TpqStudentController::class, 'import'])->name('santri.import');
                Route::post('santri/{santri}/reset-password-wali', [TpqStudentController::class, 'resetWaliPassword'])->name('santri.reset-wali-password');
                Route::post('absensi/{class}', [TpqAttendanceController::class, 'store'])->name('attendance.store');
                Route::post('hafalan/{student}', [TpqHafalanController::class, 'update'])->name('hafalan.update');
                Route::get('sertifikat', [TpqCertificateController::class, 'index'])->name('sertifikat.index');
                Route::get('sertifikat/create', [TpqCertificateController::class, 'create'])->name('sertifikat.create');
                Route::post('sertifikat', [TpqCertificateController::class, 'store'])->name('sertifikat.store');
                Route::delete('sertifikat/{sertifikat}', [TpqCertificateController::class, 'destroy'])->name('sertifikat.destroy');
            });

            // SPP — pembayaran SPP itu transaksi keuangan juga, jadi bendahara
            // (finance.create) bisa proses SPP tanpa perlu diberi tpq.manage
            // penuh (yang berarti bisa juga edit tahun ajaran/kelas/santri).
            Route::middleware('permission:tpq.manage|finance.create')->group(function () {
                Route::get('spp', [TpqSppController::class, 'index'])->name('spp.index');
                Route::post('spp/generate', [TpqSppController::class, 'generateBills'])->name('spp.generate');
                Route::post('spp/{bill}/bayar', [TpqSppController::class, 'pay'])->name('spp.pay');
                Route::post('spp/{bill}/bukti/setujui', [TpqSppController::class, 'approveProof'])->name('spp.proof.approve');
                Route::post('spp/{bill}/bukti/tolak', [TpqSppController::class, 'rejectProof'])->name('spp.proof.reject');
                Route::post('spp/kirim-reminder', [TpqSppController::class, 'sendReminders'])->name('spp.reminders');
            });

            // Nilai — permission tpq.grade sendiri (sudah ada sebelumnya), satu
            // tingkat mencakup lihat & input (tidak ada tpq.grade.view/manage terpisah).
            Route::middleware('permission:tpq.grade')->group(function () {
                Route::get('nilai', [TpqGradeController::class, 'index'])->name('grade.index');
                Route::get('nilai/{class}/{semester}', [TpqGradeController::class, 'show'])->name('grade.show');
                Route::post('nilai/{class}/{semester}', [TpqGradeController::class, 'store'])->name('grade.store');
            });

            // Raport — permission tpq.report sendiri (sudah ada sebelumnya).
            Route::middleware('permission:tpq.report')->group(function () {
                Route::get('raport', [TpqReportCardController::class, 'index'])->name('report.index');
                Route::get('raport/{semester}', [TpqReportCardController::class, 'semester'])->name('report.semester');
                Route::post('raport/{semester}/{student}/generate', [TpqReportCardController::class, 'generate'])->name('report.generate');
                Route::post('raport/{semester}/generate-all', [TpqReportCardController::class, 'generateAll'])->name('report.generate-all');
                Route::get('raport/{reportCard}/preview', [TpqReportCardController::class, 'preview'])->name('report.preview');
                Route::get('raport/{reportCard}/pdf', [TpqReportCardController::class, 'downloadPdf'])->name('report.pdf');
                Route::get('raport/{semester}/download-all', [TpqReportCardController::class, 'downloadAll'])->name('report.download-all');
                Route::post('raport/{reportCard}/kirim-wa', [TpqReportCardController::class, 'sendWhatsApp'])->name('report.send-wa');
                Route::post('raport/{semester}/kirim-wa-all', [TpqReportCardController::class, 'sendWhatsAppAll'])->name('report.send-wa-all');
            });

            // Harian (Mengaji Harian) — ustadz hanya dapat ini dari seluruh TPQ.
            Route::prefix('harian')->name('daily-progress.')->group(function () {
                Route::middleware('permission:tpq.daily-progress.view')->group(function () {
                    Route::get('/', [TpqDailyProgressController::class, 'index'])->name('index');
                    Route::get('cari', [TpqDailyProgressController::class, 'search'])->name('search');
                    Route::get('rekap', [TpqDailyProgressController::class, 'recap'])->name('recap');
                    Route::get('rekap/export', [TpqDailyProgressController::class, 'exportRecap'])->name('recap.export');
                    Route::get('santri/{student}', [TpqDailyProgressController::class, 'showStudent'])->name('santri');
                    Route::get('kelas', [TpqDailyProgressController::class, 'kelasIndex'])->name('kelas.index');
                    Route::get('kelas/{class}', [TpqDailyProgressController::class, 'show'])->name('show');
                });
                Route::middleware('permission:tpq.daily-progress.manage')->group(function () {
                    Route::post('santri/{student}', [TpqDailyProgressController::class, 'storeStudent'])->name('santri.store');
                    Route::post('santri/{student}/naik-jilid', [TpqDailyProgressController::class, 'promoteLevel'])->name('santri.promote');
                    Route::post('kelas/{class}', [TpqDailyProgressController::class, 'store'])->name('store');
                });
            });
        });

        // Presensi Staf — satu middleware dengan 2 permission (pipe): "lihat
        // punya sendiri" vs "lihat semua staf" dibedakan DI DALAM controller
        // (StaffAttendanceController), bukan di route.
        Route::prefix('presensi-staf')->name('staff-attendance.')
            ->middleware('permission:staff-attendance.view-own|staff-attendance.view-all')
            ->group(function () {
                Route::get('/', [StaffAttendanceController::class, 'index'])->name('index');
                Route::get('export', [StaffAttendanceController::class, 'export'])->name('export');
                Route::get('{attendance}/detail', [StaffAttendanceController::class, 'detail'])->name('detail');
                Route::get('{attendance}/foto/{type}', [StaffAttendanceController::class, 'photo'])->name('photo');
            });

        // Jamaah — view/manage.
        Route::prefix('jamaah')->name('jamaah.')->group(function () {
            Route::middleware('permission:jamaah.view')->group(function () {
                Route::get('/', [JamaahController::class, 'index'])->name('index');
                Route::get('{jamaah}/kartu', [JamaahController::class, 'card'])->name('card');
                Route::get('program-sosial', [SocialProgramController::class, 'index'])->name('program-sosial.index');
                Route::get('program-sosial/{programSosial}', [SocialProgramController::class, 'show'])->name('program-sosial.show');
                Route::get('program-sosial/{programSosial}/penerima/{recipient}/tanda-terima', [SocialProgramController::class, 'receipt'])->name('program-sosial.receipt');
                Route::get('program-sosial/{programSosial}/laporan', [SocialProgramController::class, 'report'])->name('program-sosial.report');
            });
            Route::middleware('permission:jamaah.manage')->group(function () {
                Route::get('tambah', [JamaahController::class, 'create'])->name('create');
                Route::post('/', [JamaahController::class, 'store'])->name('store');
                Route::get('{jamaah}/edit', [JamaahController::class, 'edit'])->name('edit');
                Route::put('{jamaah}', [JamaahController::class, 'update'])->name('update');
                Route::delete('{jamaah}', [JamaahController::class, 'destroy'])->name('destroy');
                Route::get('import/template', [JamaahController::class, 'importTemplate'])->name('import-template');
                Route::post('import', [JamaahController::class, 'import'])->name('import');
                Route::get('program-sosial/tambah', [SocialProgramController::class, 'create'])->name('program-sosial.create');
                Route::post('program-sosial', [SocialProgramController::class, 'store'])->name('program-sosial.store');
                Route::put('program-sosial/{programSosial}', [SocialProgramController::class, 'update'])->name('program-sosial.update');
                Route::delete('program-sosial/{programSosial}', [SocialProgramController::class, 'destroy'])->name('program-sosial.destroy');
                Route::post('program-sosial/{programSosial}/penerima', [SocialProgramController::class, 'addRecipient'])->name('program-sosial.penerima.store');
                Route::post('program-sosial/{programSosial}/penerima/{recipient}/distribusi', [SocialProgramController::class, 'distribute'])->name('program-sosial.distribusi');
                Route::get('broadcast', [BroadcastController::class, 'index'])->name('broadcast');
                Route::post('broadcast', [BroadcastController::class, 'send'])->name('broadcast.send');
            });
        });

        // Ramadhan — view/manage.
        Route::prefix('ramadhan')->name('ramadhan.')->group(function () {
            Route::middleware('permission:ramadhan.view')->group(function () {
                Route::get('/', [RamadhanController::class, 'index'])->name('index');
                Route::get('khatam', [KhatamTrackerController::class, 'index'])->name('khatam.index');
                Route::get('itikaf', [ItikafController::class, 'index'])->name('itikaf.index');
                Route::get('qurban', [QurbanController::class, 'index'])->name('qurban.index');
                Route::get('qurban/export-pdf', [QurbanController::class, 'exportPdf'])->name('qurban.export-pdf');
                Route::get('qurban/laporan-distribusi', [QurbanController::class, 'distributionReport'])->name('qurban.distribution-report');
                Route::get('qurban/distribusi/{distribution}/label', [QurbanController::class, 'distributionLabel'])->name('qurban.distribusi.label');
            });
            Route::middleware('permission:ramadhan.manage')->group(function () {
                Route::post('imsakiyah/generate', [RamadhanController::class, 'generateImsakiyah'])->name('imsakiyah.generate');
                Route::post('khatam', [KhatamTrackerController::class, 'store'])->name('khatam.store');
                Route::put('khatam/{khatam}', [KhatamTrackerController::class, 'update'])->name('khatam.update');
                Route::delete('khatam/{khatam}', [KhatamTrackerController::class, 'destroy'])->name('khatam.destroy');
                Route::post('itikaf', [ItikafController::class, 'store'])->name('itikaf.store');
                Route::put('itikaf/{itikaf}', [ItikafController::class, 'update'])->name('itikaf.update');
                Route::delete('itikaf/{itikaf}', [ItikafController::class, 'destroy'])->name('itikaf.destroy');
                Route::post('qurban', [QurbanController::class, 'store'])->name('qurban.store');
                Route::put('qurban/{qurban}', [QurbanController::class, 'update'])->name('qurban.update');
                Route::delete('qurban/{qurban}', [QurbanController::class, 'destroy'])->name('qurban.destroy');
                Route::post('qurban/distribusi', [QurbanController::class, 'storeDistribution'])->name('qurban.distribusi.store');
            });
        });

        // Wakaf & Pembangunan — view/manage.
        Route::prefix('wakaf')->name('wakaf.')->group(function () {
            Route::middleware('permission:wakaf.view')->group(function () {
                Route::get('/', [WakafController::class, 'index'])->name('index');
                Route::get('proyek', [BuildingProjectController::class, 'index'])->name('proyek.index');
                Route::get('proyek/{proyek}', [BuildingProjectController::class, 'show'])->name('proyek.show');
            });
            Route::middleware('permission:wakaf.manage')->group(function () {
                Route::get('tambah', [WakafController::class, 'create'])->name('create');
                Route::post('/', [WakafController::class, 'store'])->name('store');
                Route::get('{wakaf}/edit', [WakafController::class, 'edit'])->name('edit');
                Route::put('{wakaf}', [WakafController::class, 'update'])->name('update');
                Route::delete('{wakaf}', [WakafController::class, 'destroy'])->name('destroy');
                Route::get('proyek/tambah', [BuildingProjectController::class, 'create'])->name('proyek.create');
                Route::post('proyek', [BuildingProjectController::class, 'store'])->name('proyek.store');
                Route::get('proyek/{proyek}/edit', [BuildingProjectController::class, 'edit'])->name('proyek.edit');
                Route::put('proyek/{proyek}', [BuildingProjectController::class, 'update'])->name('proyek.update');
                Route::delete('proyek/{proyek}', [BuildingProjectController::class, 'destroy'])->name('proyek.destroy');
                Route::post('proyek/{proyek}/update-progress', [BuildingProjectController::class, 'storeUpdate'])->name('proyek.update-progress');
            });
        });

        // Perpustakaan — view/manage.
        Route::prefix('perpustakaan')->name('library.')->group(function () {
            Route::middleware('permission:library.view')->group(function () {
                Route::get('/', [LibraryBookController::class, 'index'])->name('index');
                Route::get('peminjaman', [LibraryLoanController::class, 'index'])->name('loans.index');
            });
            Route::middleware('permission:library.manage')->group(function () {
                Route::post('/', [LibraryBookController::class, 'store'])->name('store');
                Route::put('{buku}', [LibraryBookController::class, 'update'])->name('update');
                Route::delete('{buku}', [LibraryBookController::class, 'destroy'])->name('destroy');
                Route::post('peminjaman', [LibraryLoanController::class, 'store'])->name('loans.store');
                Route::post('peminjaman/{loan}/kembalikan', [LibraryLoanController::class, 'returnBook'])->name('loans.return');
                Route::delete('peminjaman/{loan}', [LibraryLoanController::class, 'destroy'])->name('loans.destroy');
            });
        });

        // Laporan — view saja, tidak ada aksi tulis.
        Route::middleware('permission:report.view')->prefix('laporan')->name('report.')->group(function () {
            Route::get('keuangan', [ReportController::class, 'finance'])->name('finance');
            Route::get('aset', [ReportController::class, 'asset'])->name('asset');
            Route::get('kegiatan', [ReportController::class, 'activity'])->name('activity');
            Route::get('jamaah', [ReportController::class, 'jamaah'])->name('jamaah');
            Route::get('lpj', [ReportController::class, 'lpj'])->name('lpj');
            Route::get('lpj/generate', [ReportController::class, 'generateLpj'])->name('lpj.generate');
        });

        // Pengaturan — settings.manage saja, tidak berubah (sudah 1 tingkat).
        Route::middleware('permission:settings.manage')->prefix('pengaturan')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::post('masjid', [SettingController::class, 'updateMasjid'])->name('masjid');
            Route::post('masjid/logo', [SettingController::class, 'updateLogo'])->name('masjid.logo');
            Route::delete('masjid/logo', [SettingController::class, 'removeLogo'])->name('masjid.logo.destroy');
            Route::post('masjid/background', [SettingController::class, 'updateBackground'])->name('masjid.background');
            Route::delete('masjid/background', [SettingController::class, 'removeBackground'])->name('masjid.background.destroy');
            Route::post('domain', [DomainController::class, 'update'])->name('domain');
            Route::post('domain/verify', [DomainController::class, 'verify'])->name('domain.verify');
            Route::resource('pengguna', UserController::class)->except(['show']);
            Route::get('log-aktivitas', [AuditLogController::class, 'index'])->name('audit-log');
            Route::resource('lokasi-presensi', MasjidLocationController::class)
                ->except(['show', 'create', 'edit'])
                ->parameters(['lokasi-presensi' => 'location']);
        });
    });
});
