<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Asset\AssetController;
use App\Http\Controllers\Asset\AssetLoanController;
use App\Http\Controllers\Asset\MaintenanceController;
use App\Http\Controllers\Activity\ActivityController;
use App\Http\Controllers\Activity\AttendanceController;
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
use App\Http\Controllers\Public\PublicPortalController;
use App\Http\Controllers\Ramadhan\ItikafController;
use App\Http\Controllers\Ramadhan\KhatamTrackerController;
use App\Http\Controllers\Ramadhan\QurbanController;
use App\Http\Controllers\Ramadhan\RamadhanController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Settings\AuditLogController;
use App\Http\Controllers\Settings\SettingController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Study\MajelisAnggotaController;
use App\Http\Controllers\Study\MajelisController;
use App\Http\Controllers\Study\StudySessionController;
use App\Http\Controllers\Tpq\TpqAcademicYearController;
use App\Http\Controllers\Tpq\TpqAttendanceController;
use App\Http\Controllers\Tpq\TpqCertificateController;
use App\Http\Controllers\Tpq\TpqClassController;
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
    Route::middleware('auth.wali')->group(function () {
        Route::post('/logout', [WaliController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [WaliController::class, 'dashboard'])->name('dashboard');
        Route::get('/raport/{reportCard}', [WaliController::class, 'reportCard'])->name('reportcard');
        Route::get('/raport/{reportCard}/pdf', [WaliController::class, 'reportCardPdf'])->name('reportcard.pdf');
    });
});

// === PAYMENT WEBHOOK ===
Route::post('/webhook/midtrans', [PaymentController::class, 'midtransWebhook'])->name('webhook.midtrans');

// === ADMIN PANEL ===
// Note: README menggunakan middleware ['auth', 'verified'], namun skema users pada
// proyek ini login via nomor HP dan tidak memiliki email_verified_at, sehingga
// middleware 'verified' tidak diterapkan.
Route::middleware(['auth'])->group(function () {

    // Pengumuman (tidak di-prefix admin/ agar sesuai nama route README)
    Route::resource('pengumuman', AnnouncementController::class)->except(['show']);

    Route::prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Keuangan
        Route::prefix('keuangan')->name('finance.')->group(function () {
            Route::resource('transaksi', TransactionController::class)->except(['show']);
            Route::post('transaksi/{transaksi}/approve', [TransactionController::class, 'approve'])->name('transaksi.approve');
            Route::resource('kas', KasAccountController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['kas' => 'kas']);
            Route::resource('anggaran', BudgetController::class)->except(['show']);
            Route::resource('donasi', DonationController::class)->only(['index', 'show']);
            Route::get('laporan', [FinanceReportController::class, 'index'])->name('laporan');
            Route::get('laporan/export-pdf', [FinanceReportController::class, 'exportPdf'])->name('laporan.export-pdf');
            Route::get('laporan/export-excel', [FinanceReportController::class, 'exportExcel'])->name('laporan.export-excel');

            // Zakat
            Route::prefix('zakat')->name('zakat.')->group(function () {
                Route::get('/', [ZakatController::class, 'index'])->name('index');
                Route::resource('penerimaan', ZakatController::class)->except(['index'])->parameters(['penerimaan' => 'penerimaan']);
                Route::resource('penerima', ZakatRecipientController::class)->parameters(['penerima' => 'penerima']);
            });
        });

        // Aset
        Route::prefix('aset')->name('asset.')->group(function () {
            Route::resource('inventaris', AssetController::class)->except(['show'])->parameters(['inventaris' => 'inventaris']);
            Route::get('inventaris/{inventaris}/qr', [AssetController::class, 'generateQr'])->name('inventaris.qr');
            Route::resource('peminjaman', AssetLoanController::class)->only(['index', 'create', 'store', 'destroy'])->parameters(['peminjaman' => 'loan']);
            Route::post('peminjaman/{loan}/approve', [AssetLoanController::class, 'approve'])->name('peminjaman.approve');
            Route::post('peminjaman/{loan}/return', [AssetLoanController::class, 'returnAsset'])->name('peminjaman.return');
            Route::resource('maintenance', MaintenanceController::class)->except(['show']);
        });

        // Kegiatan
        Route::prefix('kegiatan')->name('activity.')->group(function () {
            Route::get('kalender', [ActivityController::class, 'calendar'])->name('calendar');
            Route::get('tambah', [ActivityController::class, 'create'])->name('create');
            Route::post('/', [ActivityController::class, 'store'])->name('store');
            Route::get('{kegiatan}/edit', [ActivityController::class, 'edit'])->name('edit');
            Route::put('{kegiatan}', [ActivityController::class, 'update'])->name('update');
            Route::delete('{kegiatan}', [ActivityController::class, 'destroy'])->name('destroy');
            Route::get('{activity}/presensi', [AttendanceController::class, 'show'])->name('attendance');
            Route::post('{activity}/presensi', [AttendanceController::class, 'store'])->name('attendance.store');
            Route::get('{activity}/presensi/export', [AttendanceController::class, 'exportPdf'])->name('attendance.export');
            Route::get('{kegiatan}/qr', [ActivityController::class, 'qrCode'])->name('qr');
            Route::post('api/attendance-qr/{activity}', [AttendanceController::class, 'scanQr'])->name('attendance.scan');
        });

        // Shalat & Imam
        Route::prefix('shalat')->name('prayer.')->group(function () {
            Route::get('jadwal', [PrayerScheduleController::class, 'index'])->name('schedule');
            Route::post('jadwal/generate', [PrayerScheduleController::class, 'generate'])->name('schedule.generate');
            Route::resource('imam', ImamController::class)->except(['show']);
            Route::get('jadwal-imam', [ImamScheduleController::class, 'index'])->name('imam-schedule');
            Route::post('jadwal-imam', [ImamScheduleController::class, 'store'])->name('imam-schedule.store');
            Route::post('jadwal-imam/{schedule}/ganti', [ImamScheduleController::class, 'substitute'])->name('imam-schedule.substitute');
            Route::post('jadwal-imam/notify', [ImamScheduleController::class, 'notifyAll'])->name('imam-schedule.notify');
            Route::get('jadwal-imam/export-pdf', [ImamScheduleController::class, 'exportPdf'])->name('imam-schedule.export');
        });

        // Kajian & Majelis Taklim
        Route::prefix('kajian')->name('study.')->group(function () {
            Route::resource('sesi', StudySessionController::class)->except(['show']);
            Route::resource('majelis', MajelisController::class)->except(['create', 'edit'])->parameters(['majelis' => 'majelis']);
            Route::resource('majelis/{majelis}/anggota', MajelisAnggotaController::class)
                ->except(['index', 'create', 'edit', 'show'])
                ->parameters(['anggota' => 'anggota']);
        });

        // TPQ
        Route::prefix('tpq')->name('tpq.')->group(function () {
            Route::get('/', [TpqDashboardController::class, 'index'])->name('dashboard');

            Route::get('pengaturan', [TpqSettingController::class, 'edit'])->name('pengaturan.edit');
            Route::post('pengaturan', [TpqSettingController::class, 'update'])->name('pengaturan.update');

            Route::resource('tahun-ajaran', TpqAcademicYearController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->parameters(['tahun-ajaran' => 'tahunAjaran']);
            Route::resource('semester', TpqSemesterController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('kelas', TpqClassController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->parameters(['kelas' => 'kela']);

            Route::resource('santri', TpqStudentController::class)->except(['show']);
            Route::get('santri/{student}/kartu', [TpqStudentController::class, 'card'])->name('santri.card');

            Route::prefix('absensi')->name('attendance.')->group(function () {
                Route::get('/', [TpqAttendanceController::class, 'index'])->name('index');
                Route::get('{class}', [TpqAttendanceController::class, 'show'])->name('show');
                Route::post('{class}', [TpqAttendanceController::class, 'store'])->name('store');
                Route::get('{class}/rekap', [TpqAttendanceController::class, 'recap'])->name('recap');
                Route::get('{class}/rekap/export', [TpqAttendanceController::class, 'exportRecapPdf'])->name('recap.export');
            });

            Route::prefix('nilai')->name('grade.')->group(function () {
                Route::get('/', [TpqGradeController::class, 'index'])->name('index');
                Route::get('{class}/{semester}', [TpqGradeController::class, 'show'])->name('show');
                Route::post('{class}/{semester}', [TpqGradeController::class, 'store'])->name('store');
            });

            Route::prefix('hafalan')->name('hafalan.')->group(function () {
                Route::get('{student}', [TpqHafalanController::class, 'show'])->name('show');
                Route::post('{student}', [TpqHafalanController::class, 'update'])->name('update');
            });

            Route::prefix('raport')->name('report.')->group(function () {
                Route::get('/', [TpqReportCardController::class, 'index'])->name('index');
                Route::get('{semester}', [TpqReportCardController::class, 'semester'])->name('semester');
                Route::post('{semester}/{student}/generate', [TpqReportCardController::class, 'generate'])->name('generate');
                Route::post('{semester}/generate-all', [TpqReportCardController::class, 'generateAll'])->name('generate-all');
                Route::get('{reportCard}/preview', [TpqReportCardController::class, 'preview'])->name('preview');
                Route::get('{reportCard}/pdf', [TpqReportCardController::class, 'downloadPdf'])->name('pdf');
                Route::get('{semester}/download-all', [TpqReportCardController::class, 'downloadAll'])->name('download-all');
                Route::post('{reportCard}/kirim-wa', [TpqReportCardController::class, 'sendWhatsApp'])->name('send-wa');
                Route::post('{semester}/kirim-wa-all', [TpqReportCardController::class, 'sendWhatsAppAll'])->name('send-wa-all');
            });

            Route::prefix('spp')->name('spp.')->group(function () {
                Route::get('/', [TpqSppController::class, 'index'])->name('index');
                Route::post('generate', [TpqSppController::class, 'generateBills'])->name('generate');
                Route::post('{bill}/bayar', [TpqSppController::class, 'pay'])->name('pay');
                Route::post('kirim-reminder', [TpqSppController::class, 'sendReminders'])->name('reminders');
            });

            Route::resource('sertifikat', TpqCertificateController::class)->only(['index', 'create', 'store', 'destroy']);
        });

        // Jamaah
        Route::prefix('jamaah')->name('jamaah.')->group(function () {
            Route::get('/', [JamaahController::class, 'index'])->name('index');
            Route::get('tambah', [JamaahController::class, 'create'])->name('create');
            Route::post('/', [JamaahController::class, 'store'])->name('store');
            Route::get('{jamaah}/edit', [JamaahController::class, 'edit'])->name('edit');
            Route::put('{jamaah}', [JamaahController::class, 'update'])->name('update');
            Route::delete('{jamaah}', [JamaahController::class, 'destroy'])->name('destroy');
            Route::get('{jamaah}/kartu', [JamaahController::class, 'card'])->name('card');
            Route::get('import/template', [JamaahController::class, 'importTemplate'])->name('import-template');
            Route::post('import', [JamaahController::class, 'import'])->name('import');

            Route::prefix('program-sosial')->name('program-sosial.')->group(function () {
                Route::get('/', [SocialProgramController::class, 'index'])->name('index');
                Route::get('tambah', [SocialProgramController::class, 'create'])->name('create');
                Route::post('/', [SocialProgramController::class, 'store'])->name('store');
                Route::get('{programSosial}', [SocialProgramController::class, 'show'])->name('show');
                Route::put('{programSosial}', [SocialProgramController::class, 'update'])->name('update');
                Route::delete('{programSosial}', [SocialProgramController::class, 'destroy'])->name('destroy');
                Route::post('{programSosial}/penerima', [SocialProgramController::class, 'addRecipient'])->name('penerima.store');
                Route::post('{programSosial}/penerima/{recipient}/distribusi', [SocialProgramController::class, 'distribute'])->name('distribusi');
                Route::get('{programSosial}/penerima/{recipient}/tanda-terima', [SocialProgramController::class, 'receipt'])->name('receipt');
                Route::get('{programSosial}/laporan', [SocialProgramController::class, 'report'])->name('report');
            });

            Route::get('broadcast', [BroadcastController::class, 'index'])->name('broadcast');
            Route::post('broadcast', [BroadcastController::class, 'send'])->name('broadcast.send');
        });

        // Ramadhan
        Route::prefix('ramadhan')->name('ramadhan.')->group(function () {
            Route::get('/', [RamadhanController::class, 'index'])->name('index');
            Route::post('imsakiyah/generate', [RamadhanController::class, 'generateImsakiyah'])->name('imsakiyah.generate');
            Route::resource('khatam', KhatamTrackerController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('itikaf', ItikafController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('qurban', QurbanController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::get('qurban/export-pdf', [QurbanController::class, 'exportPdf'])->name('qurban.export-pdf');
            Route::post('qurban/distribusi', [QurbanController::class, 'storeDistribution'])->name('qurban.distribusi.store');
            Route::get('qurban/distribusi/{distribution}/label', [QurbanController::class, 'distributionLabel'])->name('qurban.distribusi.label');
            Route::get('qurban/laporan-distribusi', [QurbanController::class, 'distributionReport'])->name('qurban.distribution-report');
        });

        // Wakaf & Pembangunan
        Route::prefix('wakaf')->name('wakaf.')->group(function () {
            Route::get('/', [WakafController::class, 'index'])->name('index');
            Route::get('tambah', [WakafController::class, 'create'])->name('create');
            Route::post('/', [WakafController::class, 'store'])->name('store');
            Route::get('{wakaf}/edit', [WakafController::class, 'edit'])->name('edit');
            Route::put('{wakaf}', [WakafController::class, 'update'])->name('update');
            Route::delete('{wakaf}', [WakafController::class, 'destroy'])->name('destroy');

            Route::prefix('proyek')->name('proyek.')->group(function () {
                Route::get('/', [BuildingProjectController::class, 'index'])->name('index');
                Route::get('tambah', [BuildingProjectController::class, 'create'])->name('create');
                Route::post('/', [BuildingProjectController::class, 'store'])->name('store');
                Route::get('{proyek}', [BuildingProjectController::class, 'show'])->name('show');
                Route::get('{proyek}/edit', [BuildingProjectController::class, 'edit'])->name('edit');
                Route::put('{proyek}', [BuildingProjectController::class, 'update'])->name('update');
                Route::delete('{proyek}', [BuildingProjectController::class, 'destroy'])->name('destroy');
                Route::post('{proyek}/update-progress', [BuildingProjectController::class, 'storeUpdate'])->name('update-progress');
            });
        });

        // Perpustakaan
        Route::prefix('perpustakaan')->name('library.')->group(function () {
            Route::get('/', [LibraryBookController::class, 'index'])->name('index');
            Route::post('/', [LibraryBookController::class, 'store'])->name('store');
            Route::put('{buku}', [LibraryBookController::class, 'update'])->name('update');
            Route::delete('{buku}', [LibraryBookController::class, 'destroy'])->name('destroy');

            Route::prefix('peminjaman')->name('loans.')->group(function () {
                Route::get('/', [LibraryLoanController::class, 'index'])->name('index');
                Route::post('/', [LibraryLoanController::class, 'store'])->name('store');
                Route::post('{loan}/kembalikan', [LibraryLoanController::class, 'returnBook'])->name('return');
                Route::delete('{loan}', [LibraryLoanController::class, 'destroy'])->name('destroy');
            });
        });

        // Laporan
        Route::prefix('laporan')->name('report.')->group(function () {
            Route::get('keuangan', [ReportController::class, 'finance'])->name('finance');
            Route::get('aset', [ReportController::class, 'asset'])->name('asset');
            Route::get('kegiatan', [ReportController::class, 'activity'])->name('activity');
            Route::get('jamaah', [ReportController::class, 'jamaah'])->name('jamaah');
            Route::get('lpj', [ReportController::class, 'lpj'])->name('lpj');
            Route::get('lpj/generate', [ReportController::class, 'generateLpj'])->name('lpj.generate');
        });

        // Pengaturan
        Route::prefix('pengaturan')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::post('masjid', [SettingController::class, 'updateMasjid'])->name('masjid');
            Route::resource('pengguna', UserController::class)->except(['show']);
            Route::get('log-aktivitas', [AuditLogController::class, 'index'])->name('audit-log');
        });
    });
});
