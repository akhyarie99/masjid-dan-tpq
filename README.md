# SiMasjid — Sistem Informasi Manajemen Masjid
> Instruksi implementasi lengkap untuk Claude Code  
> Stack: Laravel 12 · Vue 3 · Inertia.js · PostgreSQL · Redis · Tailwind CSS 4 · **Flutter 3 (Android)**  
> Version: 1.1.0 | Responsive (Mobile-first) + Dark/Light Mode + Native Android App

---

## PETUNJUK UNTUK CLAUDE CODE

Baca seluruh dokumen ini sebelum menulis kode apapun. Implementasikan secara berurutan sesuai fase. Setiap bagian berisi spesifikasi lengkap yang harus diikuti persis. Jangan skip langkah, jangan asumsi fitur yang tidak disebutkan.

---

## DAFTAR ISI

1. [Project Setup](#1-project-setup)
2. [Struktur Folder](#2-struktur-folder)
3. [Database & Migration](#3-database--migration)
4. [Design System](#4-design-system--responsive--dark-mode)
5. [Auth & Role](#5-auth--role-management)
6. [Layout & Navigasi](#6-layout--navigasi)
7. [Modul 1 — Dashboard](#7-modul-1--dashboard)
8. [Modul 2 — Keuangan](#8-modul-2--keuangan)
9. [Modul 3 — Aset](#9-modul-3--aset)
10. [Modul 4 — Kegiatan](#10-modul-4--kegiatan)
11. [Modul 5 — Shalat & Imam](#11-modul-5--shalat--imam)
12. [Modul 6 — Kajian & TPQ](#12-modul-6--kajian--tpq)
13. [Modul 7 — Jamaah & Sosial](#13-modul-7--jamaah--sosial)
14. [Modul 8 — Ramadhan & PHBI](#14-modul-8--ramadhan--phbi)
15. [Modul 9 — Wakaf & Pembangunan](#15-modul-9--wakaf--pembangunan)
16. [Modul 10 — Laporan & Transparansi](#16-modul-10--laporan--transparansi)
17. [Portal Publik Jamaah](#17-portal-publik-jamaah)
18. [Integrasi Eksternal](#18-integrasi-eksternal)
19. [Notifikasi & Queue](#19-notifikasi--queue)
20. [Testing](#20-testing)
21. [Deployment](#21-deployment)
22. [Flutter Android App — Ustadz/Ustadzah](#22-flutter-android-app--ustadzustadzah)

---

## 1. PROJECT SETUP

### 1.1 Inisialisasi Laravel

```bash
composer create-project laravel/laravel simasjid
cd simasjid

# Core packages
composer require inertiajs/inertia-laravel
composer require tightenco/ziggy
composer require spatie/laravel-permission
composer require spatie/laravel-medialibrary
composer require spatie/laravel-activitylog
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel
composer require simplesoftwareio/simple-qrcode
composer require intervention/image-laravel
composer require laravel/reverb
composer require laravel/horizon

# Dev packages
composer require --dev laravel/telescope
```

### 1.2 Frontend

```bash
npm install

# Core
npm install vue@3 @inertiajs/vue3 @vitejs/plugin-vue

# UI & Styling
npm install tailwindcss @tailwindcss/vite tailwindcss/nesting
npm install @headlessui/vue
npm install lucide-vue-next

# Utilities
npm install @vueuse/core
npm install pinia
npm install dayjs

# Charts & Calendar
npm install chart.js vue-chartjs
npm install @fullcalendar/vue3 @fullcalendar/daygrid @fullcalendar/timegrid @fullcalendar/interaction

# Prayer times
npm install adhan

# Hijri calendar
npm install moment-hijri
```

### 1.3 Environment `.env`

```env
APP_NAME=SiMasjid
APP_URL=https://simasjid.test

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=simasjid
DB_USERNAME=postgres
DB_PASSWORD=secret

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

BROADCAST_DRIVER=reverb
REVERB_APP_ID=simasjid
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080

# WhatsApp Gateway
FONNTE_TOKEN=

# Payment Gateway
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false

# Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=auto
AWS_BUCKET=
AWS_URL=
AWS_ENDPOINT= # Cloudflare R2 endpoint

# Email
MAIL_MAILER=resend
RESEND_API_KEY=

# Masjid default coordinates (update via admin)
MASJID_LATITUDE=-7.4894
MASJID_LONGITUDE=109.0044
```

---

## 2. STRUKTUR FOLDER

```
app/
├── Console/Commands/
│   ├── GeneratePrayerSchedule.php      # hitung jadwal shalat harian
│   ├── SendMaintenanceReminders.php    # reminder H-7 aset
│   ├── GenerateSppBills.php            # buat tagihan SPP bulanan
│   └── SendSppReminders.php            # reminder SPP ke wali
├── Http/Controllers/
│   ├── Auth/
│   ├── Dashboard/
│   ├── Finance/
│   │   ├── TransactionController.php
│   │   ├── BudgetController.php
│   │   ├── DonationController.php
│   │   └── ZakatController.php
│   ├── Asset/
│   │   ├── AssetController.php
│   │   ├── AssetLoanController.php
│   │   └── MaintenanceController.php
│   ├── Activity/
│   │   ├── ActivityController.php
│   │   └── AttendanceController.php
│   ├── Prayer/
│   │   ├── PrayerScheduleController.php
│   │   └── ImamController.php
│   ├── Learning/
│   │   ├── StudySessionController.php
│   │   └── MajelisController.php
│   ├── Tpq/
│   │   ├── TpqStudentController.php
│   │   ├── TpqAttendanceController.php
│   │   ├── TpqGradeController.php
│   │   ├── TpqReportCardController.php
│   │   └── TpqSppController.php
│   ├── Jamaah/
│   │   ├── JamaahController.php
│   │   └── SocialProgramController.php
│   ├── Ramadhan/
│   │   └── RamadhanController.php
│   ├── Wakaf/
│   │   └── WakafController.php
│   ├── Report/
│   │   └── ReportController.php
│   └── Public/
│       └── PublicPortalController.php
├── Models/
├── Services/
│   ├── PrayerTimeService.php           # kalkulasi adzan dengan adhan-php
│   ├── WhatsAppService.php             # Fonnte API wrapper
│   ├── PaymentService.php              # Midtrans wrapper
│   ├── TpqReportCardService.php        # generate PDF raport
│   └── NotificationService.php
├── Jobs/
│   ├── SendWhatsAppNotification.php
│   ├── GenerateReportCard.php
│   └── SendDonationReceipt.php
└── Policies/

resources/
├── js/
│   ├── app.js
│   ├── bootstrap.js
│   ├── Layouts/
│   │   ├── AdminLayout.vue             # layout utama admin (sidebar + topbar)
│   │   ├── PublicLayout.vue            # layout portal publik
│   │   └── PrintLayout.vue             # layout khusus cetak/PDF preview
│   ├── Components/
│   │   ├── UI/                         # komponen atom
│   │   │   ├── AppButton.vue
│   │   │   ├── AppInput.vue
│   │   │   ├── AppSelect.vue
│   │   │   ├── AppModal.vue
│   │   │   ├── AppTable.vue
│   │   │   ├── AppBadge.vue
│   │   │   ├── AppCard.vue
│   │   │   ├── AppPagination.vue
│   │   │   ├── AppAlert.vue
│   │   │   └── AppToast.vue
│   │   ├── Shared/
│   │   │   ├── ThemeToggle.vue         # tombol dark/light mode
│   │   │   ├── SidebarNav.vue
│   │   │   ├── TopBar.vue
│   │   │   ├── MobileBottomNav.vue     # navigasi bawah untuk mobile
│   │   │   ├── PageHeader.vue
│   │   │   ├── StatCard.vue
│   │   │   ├── EmptyState.vue
│   │   │   └── ConfirmDialog.vue
│   │   ├── Finance/
│   │   ├── Asset/
│   │   ├── Activity/
│   │   ├── Prayer/
│   │   ├── Tpq/
│   │   │   ├── AttendanceGrid.vue      # grid absensi kelas
│   │   │   ├── GradeInput.vue          # input nilai per mapel
│   │   │   ├── HafalanTracker.vue      # progress hafalan surah
│   │   │   ├── ReportCardPreview.vue   # preview raport sebelum cetak
│   │   │   └── SppStatus.vue           # status SPP per santri
│   │   └── Public/
│   │       ├── PrayerCountdown.vue     # countdown waktu shalat
│   │       ├── DigitalClock.vue        # jam digital besar
│   │       └── DonationWidget.vue
│   ├── Pages/
│   │   ├── Auth/
│   │   │   ├── Login.vue
│   │   │   └── ForgotPassword.vue
│   │   ├── Dashboard/
│   │   │   └── Index.vue
│   │   ├── Finance/
│   │   │   ├── Index.vue
│   │   │   ├── Transactions/
│   │   │   ├── Budget/
│   │   │   ├── Donation/
│   │   │   └── Zakat/
│   │   ├── Asset/
│   │   ├── Activity/
│   │   ├── Prayer/
│   │   ├── Learning/
│   │   │   └── Tpq/
│   │   │       ├── Dashboard.vue
│   │   │       ├── Students/
│   │   │       ├── Attendance/
│   │   │       ├── Grades/
│   │   │       ├── ReportCards/
│   │   │       └── Spp/
│   │   ├── Jamaah/
│   │   ├── Ramadhan/
│   │   ├── Wakaf/
│   │   ├── Report/
│   │   ├── Settings/
│   │   └── Public/
│   │       └── Portal.vue
│   ├── composables/
│   │   ├── useDarkMode.js              # dark/light mode composable
│   │   ├── usePrayerTime.js
│   │   ├── useFlash.js
│   │   └── usePermission.js
│   └── stores/
│       ├── theme.js                    # Pinia store untuk tema
│       └── auth.js
├── css/
│   └── app.css
└── views/
    ├── app.blade.php
    ├── pdf/
    │   ├── report-card.blade.php       # template raport TPQ
    │   ├── financial-report.blade.php
    │   ├── asset-inventory.blade.php
    │   └── certificate.blade.php
    └── emails/
```

---

## 3. DATABASE & MIGRATION

Buat migrations dalam urutan berikut. Semua tabel menggunakan UUID sebagai primary key (`$table->uuid('id')->primary()`).

### 3.1 Core Tables

```php
// migrations/create_masjids_table.php
Schema::create('masjids', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('address');
    $table->decimal('latitude', 10, 7)->nullable();
    $table->decimal('longitude', 10, 7)->nullable();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->string('website')->nullable();
    $table->string('instagram')->nullable();
    $table->string('youtube')->nullable();
    $table->text('vision')->nullable();
    $table->text('mission')->nullable();
    $table->string('prayer_method')->default('kemenag'); // kemenag|mwl|isna
    $table->json('bank_accounts')->nullable(); // [{bank, no_rekening, atas_nama}]
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// migrations/create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->string('name');
    $table->string('email')->unique()->nullable();
    $table->string('phone')->unique();
    $table->string('password');
    $table->string('avatar')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->foreign('masjid_id')->references('id')->on('masjids');
});

// migrations/create_announcements_table.php
Schema::create('announcements', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->uuid('user_id');
    $table->string('title');
    $table->text('content');
    $table->enum('type', ['umum', 'kegiatan', 'duka', 'urgent'])->default('umum');
    $table->boolean('is_published')->default(false);
    $table->timestamp('published_at')->nullable();
    $table->timestamp('expired_at')->nullable();
    $table->boolean('send_whatsapp')->default(false);
    $table->timestamps();
});
```

### 3.2 Finance Tables

```php
// kas_accounts
Schema::create('kas_accounts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->string('name'); // Kas Tunai, Rekening BRI, dll
    $table->string('type'); // cash|bank
    $table->string('bank_name')->nullable();
    $table->string('account_number')->nullable();
    $table->string('account_name')->nullable();
    $table->decimal('initial_balance', 15, 2)->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// transaction_categories
Schema::create('transaction_categories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->string('name');
    $table->enum('type', ['income', 'expense']);
    $table->string('icon')->nullable();
    $table->string('color')->nullable();
    $table->boolean('is_system')->default(false); // kategori bawaan tidak bisa hapus
    $table->timestamps();
});

// transactions
Schema::create('transactions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->uuid('kas_account_id');
    $table->uuid('category_id');
    $table->uuid('user_id'); // pencatat
    $table->uuid('approved_by')->nullable();
    $table->string('reference_number')->unique();
    $table->enum('type', ['income', 'expense']);
    $table->decimal('amount', 15, 2);
    $table->text('description');
    $table->string('proof_file')->nullable(); // bukti transaksi
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->date('transaction_date');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

// budgets (RAB)
Schema::create('budgets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->string('name');
    $table->enum('period_type', ['monthly', 'yearly', 'project']);
    $table->date('start_date');
    $table->date('end_date');
    $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
    $table->timestamps();
});

Schema::create('budget_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('budget_id');
    $table->uuid('category_id');
    $table->string('name');
    $table->decimal('planned_amount', 15, 2);
    $table->text('notes')->nullable();
    $table->timestamps();
});

// donations
Schema::create('donations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->string('donor_name')->nullable(); // null = anonim
    $table->string('donor_phone')->nullable();
    $table->string('purpose')->nullable(); // renovasi, umum, dll
    $table->decimal('amount', 15, 2);
    $table->string('payment_method'); // qris|va_bri|va_bni|cash
    $table->string('payment_gateway_id')->nullable();
    $table->enum('status', ['pending', 'paid', 'failed', 'expired'])->default('pending');
    $table->json('gateway_response')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->boolean('receipt_sent')->default(false);
    $table->timestamps();
});

// zakat
Schema::create('zakat_records', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->enum('type', ['fitrah', 'maal', 'profesi', 'infaq']);
    $table->string('payer_name');
    $table->string('payer_phone')->nullable();
    $table->integer('dependents')->default(1); // jumlah jiwa (untuk fitrah)
    $table->decimal('amount_per_person', 10, 2)->nullable();
    $table->decimal('total_amount', 15, 2);
    $table->enum('payment_type', ['beras', 'uang'])->default('uang');
    $table->decimal('rice_kg', 8, 2)->nullable();
    $table->integer('year');
    $table->boolean('ramadhan')->default(true);
    $table->timestamps();
});

Schema::create('zakat_recipients', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->string('name');
    $table->string('phone')->nullable();
    $table->string('address');
    $table->enum('category', ['fakir', 'miskin', 'amil', 'muallaf', 'riqab', 'gharimin', 'fisabilillah', 'ibnus_sabil']);
    $table->text('notes')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 3.3 Asset Tables

```php
Schema::create('asset_categories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->string('name');
    $table->string('icon')->nullable();
    $table->timestamps();
});

Schema::create('assets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->uuid('category_id');
    $table->string('name');
    $table->string('asset_code')->unique();
    $table->string('brand')->nullable();
    $table->string('model')->nullable();
    $table->string('serial_number')->nullable();
    $table->string('location'); // ruang/lokasi di masjid
    $table->enum('condition', ['baik', 'cukup', 'rusak_ringan', 'rusak_berat']);
    $table->enum('status', ['aktif', 'dipinjam', 'perbaikan', 'dihapus'])->default('aktif');
    $table->decimal('purchase_price', 15, 2)->nullable();
    $table->date('purchase_date')->nullable();
    $table->string('vendor')->nullable();
    $table->text('description')->nullable();
    $table->string('qr_code_path')->nullable();
    $table->integer('maintenance_interval_days')->nullable(); // interval perawatan rutin
    $table->date('next_maintenance_date')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('asset_maintenances', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('asset_id');
    $table->uuid('reported_by');
    $table->uuid('handled_by')->nullable();
    $table->enum('type', ['scheduled', 'repair', 'inspection']);
    $table->text('description');
    $table->text('action_taken')->nullable();
    $table->decimal('cost', 15, 2)->default(0);
    $table->enum('status', ['scheduled', 'in_progress', 'done'])->default('scheduled');
    $table->date('scheduled_date');
    $table->date('completed_date')->nullable();
    $table->timestamps();
});

Schema::create('asset_loans', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('asset_id');
    $table->uuid('requested_by');
    $table->uuid('approved_by')->nullable();
    $table->string('borrower_name');
    $table->string('borrower_phone');
    $table->string('purpose');
    $table->date('loan_date');
    $table->date('return_date_planned');
    $table->date('return_date_actual')->nullable();
    $table->enum('condition_out', ['baik', 'cukup', 'rusak_ringan']);
    $table->enum('condition_in', ['baik', 'cukup', 'rusak_ringan', 'rusak_berat'])->nullable();
    $table->enum('status', ['pending', 'approved', 'active', 'returned', 'overdue'])->default('pending');
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### 3.4 Activity Tables

```php
Schema::create('activities', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->uuid('user_id');
    $table->string('name');
    $table->text('description')->nullable();
    $table->enum('category', ['kajian_rutin', 'pengajian_akbar', 'sosial', 'phbi', 'rapat', 'lainnya']);
    $table->string('location');
    $table->dateTime('start_at');
    $table->dateTime('end_at')->nullable();
    $table->string('pic_name')->nullable();
    $table->string('pic_phone')->nullable();
    $table->integer('quota')->nullable(); // null = unlimited
    $table->enum('status', ['draft', 'published', 'ongoing', 'done', 'cancelled'])->default('draft');
    $table->string('registration_link')->nullable();
    $table->string('qr_code_path')->nullable(); // QR untuk absensi
    $table->string('streaming_url')->nullable();
    $table->timestamps();
});

Schema::create('activity_registrations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('activity_id');
    $table->string('name');
    $table->string('phone');
    $table->string('email')->nullable();
    $table->boolean('is_attended')->default(false);
    $table->timestamp('attended_at')->nullable();
    $table->boolean('reminder_sent')->default(false);
    $table->timestamps();
});
```

### 3.5 Prayer & Imam Tables

```php
Schema::create('prayer_schedules', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->date('date');
    $table->time('fajr');
    $table->time('sunrise');
    $table->time('dhuhr');
    $table->time('asr');
    $table->time('maghrib');
    $table->time('isha');
    $table->timestamps();
    $table->unique(['masjid_id', 'date']);
});

Schema::create('imams', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->string('name');
    $table->string('phone')->nullable();
    $table->string('photo')->nullable();
    $table->enum('type', ['tetap', 'cadangan', 'tamu']);
    $table->text('bio')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('imam_schedules', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->uuid('imam_id');
    $table->uuid('substitute_imam_id')->nullable();
    $table->date('date');
    $table->enum('prayer', ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha', 'jumuah', 'tarawih']);
    $table->boolean('is_khatib')->default(false); // untuk Jumat
    $table->text('khutbah_theme')->nullable();
    $table->boolean('reminder_sent')->default(false);
    $table->boolean('is_substituted')->default(false);
    $table->timestamps();
    $table->unique(['masjid_id', 'date', 'prayer']);
});
```

### 3.6 TPQ Tables

```php
Schema::create('tpq_settings', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id')->unique();
    $table->string('name'); // nama lembaga TPQ
    $table->string('sk_number')->nullable(); // nomor SK pendirian
    $table->string('head_name'); // kepala TPQ
    $table->string('head_nip')->nullable();
    $table->string('head_signature')->nullable(); // path ttd
    $table->string('logo')->nullable();
    $table->text('address')->nullable();
    $table->json('grade_scale')->nullable(); // {min: 0, max: 100} atau {A,B,C,D}
    $table->integer('min_attendance_percent')->default(75);
    $table->integer('min_avg_grade')->default(70);
    $table->timestamps();
});

Schema::create('tpq_academic_years', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->string('name'); // "2025/2026"
    $table->date('start_date');
    $table->date('end_date');
    $table->boolean('is_active')->default(false);
    $table->timestamps();
});

Schema::create('tpq_semesters', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('academic_year_id');
    $table->integer('number'); // 1 atau 2
    $table->string('name'); // "Semester 1"
    $table->date('start_date');
    $table->date('end_date');
    $table->boolean('is_active')->default(false);
    $table->timestamps();
});

Schema::create('tpq_classes', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->string('name'); // Pra-TPQ, Iqra 1, Iqra 2, Al-Quran, Tahfidz
    $table->integer('order')->default(0);
    $table->integer('capacity')->default(20);
    $table->string('room')->nullable();
    $table->json('schedule')->nullable(); // [{day: 'senin', time: '15:30'}]
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('tpq_class_teachers', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('class_id');
    $table->uuid('academic_year_id');
    $table->uuid('teacher_id'); // user_id
    $table->boolean('is_homeroom')->default(false); // wali kelas
    $table->timestamps();
});

Schema::create('tpq_students', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->string('nis')->unique(); // nomor induk santri
    $table->string('name');
    $table->string('nik')->nullable();
    $table->string('birth_place')->nullable();
    $table->date('birth_date')->nullable();
    $table->enum('gender', ['L', 'P']);
    $table->text('address')->nullable();
    $table->string('photo')->nullable();
    $table->string('father_name')->nullable();
    $table->string('mother_name')->nullable();
    $table->string('guardian_name')->nullable();
    $table->string('guardian_phone');
    $table->string('guardian_whatsapp')->nullable();
    $table->string('parent_occupation')->nullable();
    $table->enum('status', ['aktif', 'cuti', 'lulus', 'keluar'])->default('aktif');
    $table->date('entry_date');
    $table->date('exit_date')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('tpq_student_classes', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('student_id');
    $table->uuid('class_id');
    $table->uuid('academic_year_id');
    $table->boolean('is_promoted')->nullable(); // null=belum, true=naik, false=tinggal
    $table->timestamps();
    $table->unique(['student_id', 'academic_year_id']);
});

Schema::create('tpq_subjects', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->string('name'); // Bacaan Al-Quran, Hafalan, Adab, dll
    $table->string('code')->nullable();
    $table->integer('weight')->default(1); // bobot untuk rata-rata
    $table->text('description')->nullable();
    $table->integer('order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('tpq_attendances', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('student_id');
    $table->uuid('class_id');
    $table->date('date');
    $table->enum('status', ['hadir', 'izin', 'sakit', 'alfa'])->default('hadir');
    $table->text('notes')->nullable();
    $table->uuid('recorded_by');
    $table->timestamps();
    $table->unique(['student_id', 'date']);
});

Schema::create('tpq_grades', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('student_id');
    $table->uuid('class_id');
    $table->uuid('subject_id');
    $table->uuid('semester_id');
    $table->decimal('score', 5, 2)->nullable();
    $table->string('grade_letter')->nullable(); // A/B/C/D jika pakai huruf
    $table->text('description')->nullable(); // narasi perkembangan
    $table->uuid('graded_by');
    $table->timestamps();
    $table->unique(['student_id', 'subject_id', 'semester_id']);
});

Schema::create('tpq_hafalan_progress', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('student_id');
    $table->integer('surah_number'); // 1–114
    $table->string('surah_name');
    $table->integer('total_ayah');
    $table->integer('memorized_ayah')->default(0);
    $table->enum('status', ['belum', 'sedang', 'hafal'])->default('belum');
    $table->date('memorized_date')->nullable();
    $table->uuid('verified_by')->nullable();
    $table->timestamps();
    $table->unique(['student_id', 'surah_number']);
});

Schema::create('tpq_report_cards', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('student_id');
    $table->uuid('class_id');
    $table->uuid('semester_id');
    $table->decimal('average_score', 5, 2)->nullable();
    $table->string('grade_rank')->nullable(); // peringkat opsional
    $table->integer('present_count')->default(0);
    $table->integer('sick_count')->default(0);
    $table->integer('permission_count')->default(0);
    $table->integer('absent_count')->default(0);
    $table->text('homeroom_notes')->nullable();      // catatan wali kelas
    $table->text('head_notes')->nullable();           // catatan kepala TPQ
    $table->enum('promotion_status', ['naik', 'tinggal', 'lulus'])->nullable();
    $table->string('pdf_path')->nullable();
    $table->boolean('is_distributed')->default(false);
    $table->timestamp('distributed_at')->nullable();
    $table->timestamps();
    $table->unique(['student_id', 'semester_id']);
});

Schema::create('tpq_spp_bills', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('student_id');
    $table->integer('year');
    $table->integer('month'); // 1–12
    $table->decimal('amount', 10, 2);
    $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
    $table->decimal('paid_amount', 10, 2)->default(0);
    $table->boolean('is_scholarship')->default(false); // bebas SPP
    $table->boolean('reminder_sent')->default(false);
    $table->timestamps();
    $table->unique(['student_id', 'year', 'month']);
});

Schema::create('tpq_spp_payments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('bill_id');
    $table->uuid('received_by');
    $table->decimal('amount', 10, 2);
    $table->date('paid_date');
    $table->string('payment_method')->default('cash');
    $table->string('receipt_number')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});

Schema::create('tpq_certificates', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('student_id');
    $table->enum('type', ['khatam_iqra', 'khatam_quran', 'tahfidz', 'ijazah']);
    $table->string('certificate_number')->unique();
    $table->date('issued_date');
    $table->string('achievement')->nullable(); // "Khatam Iqra 6", "Hafal Juz 30"
    $table->string('pdf_path')->nullable();
    $table->uuid('issued_by');
    $table->timestamps();
});
```

### 3.7 Jamaah & Sosial Tables

```php
Schema::create('jamaah_profiles', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('masjid_id');
    $table->uuid('user_id')->nullable(); // jika punya akun
    $table->string('name');
    $table->string('nik')->nullable();
    $table->date('birth_date')->nullable();
    $table->string('phone');
    $table->text('address')->nullable();
    $table->string('rt')->nullable();
    $table->string('rw')->nullable();
    $table->string('photo')->nullable();
    $table->enum('status', ['aktif', 'luar', 'musafir'])->default('aktif');
    $table->json('tags')->nullable(); // ['dhuafa', 'mualaf', 'hafidz', 'lansia']
    $table->boolean('receive_notification')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

### 3.8 Seeder Data Awal

```php
// database/seeders/DatabaseSeeder.php
// Jalankan: php artisan db:seed

// 1. MasjidSeeder - data masjid default
// 2. RolePermissionSeeder - roles: super_admin, admin, bendahara, sekretaris, ustadz, viewer
// 3. UserSeeder - super admin default (admin@simasjid.test / password)
// 4. TransactionCategorySeeder - kategori bawaan:
//    Income: Infaq Jumat, Donasi, Zakat Fitrah, Zakat Maal, Wakaf, Sewa Aset, Lainnya
//    Expense: Listrik, Air, Kebersihan, Keamanan, Honorarium Imam, Honorarium Marbot,
//             Perlengkapan Ibadah, Kegiatan, Renovasi, Sosial, Lainnya
// 5. AssetCategorySeeder - kategori: Bangunan, Elektronik, Furnitur, Perlengkapan Ibadah,
//                          Kendaraan, Alat Kebersihan, Tanah, Lainnya
// 6. TpqSubjectSeeder - mapel default:
//    Bacaan Al-Quran (Makhorijul Huruf & Tajwid), Hafalan Surah/Juz,
//    Adab & Akhlaq, Doa Harian & Ibadah Praktis, Pengetahuan Islam
// 7. PrayerScheduleSeeder - generate 1 bulan ke depan
```

---

## 4. DESIGN SYSTEM — RESPONSIVE & DARK MODE

### 4.1 Tailwind Config

```js
// tailwind.config.js
export default {
  darkMode: 'class', // toggle via class .dark pada <html>
  content: ['./resources/**/*.{js,vue,blade.php}'],
  theme: {
    extend: {
      colors: {
        // Brand — Islamic green palette
        primary: {
          50:  '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',  // main brand
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
          950: '#052e16',
        },
        gold: {
          400: '#fbbf24',
          500: '#f59e0b',
          600: '#d97706',
        },
        // Dark mode surfaces
        dark: {
          bg:      '#0f172a',  // slate-900
          surface: '#1e293b',  // slate-800
          border:  '#334155',  // slate-700
          muted:   '#475569',  // slate-600
        }
      },
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'sans-serif'],
        arabic: ['Amiri', 'serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
      screens: {
        'xs': '375px',
        'sm': '640px',
        'md': '768px',
        'lg': '1024px',
        'xl': '1280px',
        '2xl': '1536px',
      }
    }
  }
}
```

### 4.2 Dark Mode Composable

```js
// resources/js/composables/useDarkMode.js
import { ref, watch, onMounted } from 'vue'

const isDark = ref(false)

export function useDarkMode() {
  onMounted(() => {
    const stored = localStorage.getItem('simasjid-theme')
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    isDark.value = stored ? stored === 'dark' : prefersDark
    applyTheme()
  })

  function applyTheme() {
    if (isDark.value) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
    localStorage.setItem('simasjid-theme', isDark.value ? 'dark' : 'light')
  }

  function toggleTheme() {
    isDark.value = !isDark.value
    applyTheme()
  }

  return { isDark, toggleTheme }
}
```

### 4.3 CSS Variabel Global

```css
/* resources/css/app.css */
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Amiri:ital,wght@0,400;0,700;1,400&display=swap');

@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  /* Light mode default */
  :root {
    --bg-base:      theme('colors.slate.50');
    --bg-surface:   theme('colors.white');
    --bg-muted:     theme('colors.slate.100');
    --text-primary: theme('colors.slate.900');
    --text-muted:   theme('colors.slate.500');
    --border:       theme('colors.slate.200');
  }

  /* Dark mode override */
  .dark {
    --bg-base:      theme('colors.dark.bg');
    --bg-surface:   theme('colors.dark.surface');
    --bg-muted:     theme('colors.dark.border');
    --text-primary: theme('colors.slate.100');
    --text-muted:   theme('colors.slate.400');
    --border:       theme('colors.dark.border');
  }

  body {
    @apply bg-[var(--bg-base)] text-[var(--text-primary)] transition-colors duration-200;
    font-family: 'Plus Jakarta Sans', sans-serif;
  }

  /* Scrollbar dark mode */
  .dark ::-webkit-scrollbar-track { @apply bg-dark-surface; }
  .dark ::-webkit-scrollbar-thumb { @apply bg-dark-muted rounded-full; }
}

@layer components {
  /* Card */
  .card {
    @apply bg-[var(--bg-surface)] border border-[var(--border)] rounded-2xl shadow-sm;
  }

  /* Input */
  .input {
    @apply w-full px-3 py-2 rounded-lg border border-[var(--border)] bg-[var(--bg-surface)]
           text-[var(--text-primary)] placeholder-[var(--text-muted)]
           focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent
           transition-all duration-150 text-sm;
  }

  /* Button */
  .btn-primary {
    @apply inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700
           text-white font-medium text-sm transition-colors duration-150 disabled:opacity-50;
  }
  .btn-secondary {
    @apply inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[var(--bg-muted)]
           hover:bg-[var(--border)] text-[var(--text-primary)] font-medium text-sm transition-colors;
  }
  .btn-danger {
    @apply inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600
           text-white font-medium text-sm transition-colors;
  }

  /* Badge */
  .badge { @apply inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium; }
  .badge-green  { @apply badge bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200; }
  .badge-red    { @apply badge bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200; }
  .badge-yellow { @apply badge bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200; }
  .badge-blue   { @apply badge bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200; }
  .badge-gray   { @apply badge bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200; }

  /* Table */
  .table-responsive { @apply w-full overflow-x-auto rounded-xl; }
  .table { @apply w-full text-sm; }
  .table th { @apply px-4 py-3 text-left font-semibold text-[var(--text-muted)] bg-[var(--bg-muted)] border-b border-[var(--border)] whitespace-nowrap; }
  .table td { @apply px-4 py-3 border-b border-[var(--border)] text-[var(--text-primary)]; }
  .table tr:last-child td { @apply border-b-0; }
  .table tbody tr { @apply hover:bg-[var(--bg-muted)] transition-colors; }
}
```

### 4.4 Responsive Breakpoint Rules

**WAJIB diterapkan pada setiap komponen:**
- Mobile (`< 768px`): 1 kolom, padding kecil, font lebih kecil, tabel scroll horizontal, sidebar hidden → bottom nav
- Tablet (`768px – 1023px`): 2 kolom untuk card, sidebar collapsed (icon only)
- Desktop (`>= 1024px`): sidebar full, multi-kolom grid

```vue
<!-- Contoh grid responsive yang HARUS digunakan -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
  <!-- stat cards -->
</div>

<!-- Tabel selalu dibungkus -->
<div class="table-responsive">
  <table class="table">...</table>
</div>
```

---

## 5. AUTH & ROLE MANAGEMENT

### 5.1 Roles & Permissions

```php
// Roles:
// super_admin  → akses penuh semua fitur
// admin        → akses hampir semua, kecuali hapus data kritikal
// bendahara    → akses modul keuangan + laporan
// sekretaris   → kegiatan, jamaah, pengumuman
// ustadz       → TPQ (kelas sendiri), kajian
// viewer       → read-only semua modul

// Permission groups:
// finance.view / finance.create / finance.approve / finance.delete
// asset.view / asset.create / asset.edit / asset.delete
// activity.view / activity.create / activity.edit
// prayer.view / prayer.manage
// tpq.view / tpq.manage / tpq.grade / tpq.report
// jamaah.view / jamaah.manage
// report.view / report.export
// settings.manage
```

### 5.2 Login Page

```vue
<!-- resources/js/Pages/Auth/Login.vue -->
<!-- Desain: -->
<!-- Split layout desktop: kiri ilustrasi masjid islami, kanan form -->
<!-- Mobile: full form dengan logo di atas -->
<!-- Fields: Nomor HP atau Email, Password -->
<!-- Remember me checkbox -->
<!-- Link lupa password -->
<!-- Dark/Light toggle di pojok kanan atas -->
```

---

## 6. LAYOUT & NAVIGASI

### 6.1 AdminLayout.vue

```vue
<!-- resources/js/Layouts/AdminLayout.vue -->
<!--
STRUKTUR:
<div class="min-h-screen flex">
  <Sidebar />           ← hidden di mobile, fixed di desktop
  <div class="flex-1 flex flex-col min-w-0">
    <TopBar />          ← sticky, berisi: hamburger (mobile), breadcrumb, notif, avatar, theme toggle
    <main class="flex-1 p-4 md:p-6 overflow-auto">
      <slot />
    </main>
  </div>
  <MobileBottomNav />   ← hanya muncul di mobile (md:hidden)
</div>

SIDEBAR (desktop):
- Lebar 260px fixed
- Logo SiMasjid + nama masjid di header
- Nav items dengan icon + label (collapsible submenu)
- Footer: avatar user, role, tombol logout
- Hover state: bg-primary-50 dark:bg-primary-900/20
- Active state: bg-primary-600 text-white (kiri border accent 3px)

TOPBAR:
- Tinggi 64px
- Kiri: hamburger (mobile) / breadcrumb (desktop)
- Kanan: icon notifikasi (badge count), avatar dropdown (profil, pengaturan, logout),
         ThemeToggle (ikon matahari/bulan, animated)

MOBILE BOTTOM NAV:
- Fixed bottom, 5 item: Dashboard, Keuangan, Kegiatan, TPQ, More (→ sheet)
- Active indicator: dot atau underline hijau
- Tinggi 64px + safe-area-inset-bottom untuk notch HP
-->
```

### 6.2 ThemeToggle.vue

```vue
<template>
  <button
    @click="toggleTheme"
    class="p-2 rounded-lg bg-[var(--bg-muted)] hover:bg-[var(--border)] transition-colors"
    :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
  >
    <!-- Sun icon saat dark, Moon icon saat light -->
    <SunIcon v-if="isDark" class="w-5 h-5 text-yellow-400" />
    <MoonIcon v-else class="w-5 h-5 text-slate-600" />
  </button>
</template>

<script setup>
import { SunIcon, MoonIcon } from 'lucide-vue-next'
import { useDarkMode } from '@/composables/useDarkMode'
const { isDark, toggleTheme } = useDarkMode()
</script>
```

### 6.3 Route Structure

```php
// routes/web.php

// === PUBLIC PORTAL ===
Route::get('/', [PublicPortalController::class, 'index'])->name('home');
Route::get('/donasi', [PublicPortalController::class, 'donation'])->name('public.donation');
Route::post('/donasi', [DonationController::class, 'publicStore'])->name('public.donation.store');
Route::get('/laporan-keuangan', [PublicPortalController::class, 'financialReport'])->name('public.finance');
Route::get('/jadwal-imam', [PublicPortalController::class, 'imamSchedule'])->name('public.imam');
Route::get('/kegiatan', [PublicPortalController::class, 'activities'])->name('public.activities');
Route::get('/jam-digital', [PublicPortalController::class, 'digitalClock'])->name('public.clock');
Route::get('/daftar-kegiatan/{activity}', [ActivityController::class, 'publicRegister'])->name('public.activity.register');

// === AUTH ===
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// === PORTAL WALI MURID TPQ ===
Route::prefix('wali')->name('wali.')->group(function () {
    Route::get('/login', [WaliController::class, 'showLogin'])->name('login');
    Route::post('/login', [WaliController::class, 'login']);
    Route::middleware('auth.wali')->group(function () {
        Route::get('/dashboard', [WaliController::class, 'dashboard'])->name('dashboard');
        Route::get('/raport/{student}', [WaliController::class, 'reportCard'])->name('reportcard');
    });
});

// === ADMIN PANEL ===
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Keuangan
    Route::prefix('keuangan')->name('finance.')->group(function () {
        Route::resource('transaksi', TransactionController::class);
        Route::post('transaksi/{transaction}/approve', [TransactionController::class, 'approve']);
        Route::resource('kas', KasAccountController::class);
        Route::resource('anggaran', BudgetController::class);
        Route::resource('donasi', DonationController::class)->only(['index', 'show']);
        Route::prefix('zakat')->name('zakat.')->group(function () {
            Route::get('/', [ZakatController::class, 'index'])->name('index');
            Route::resource('penerimaan', ZakatController::class)->except('index');
            Route::resource('penerima', ZakatRecipientController::class);
        });
        Route::get('laporan', [FinanceReportController::class, 'index'])->name('report');
        Route::get('laporan/export-pdf', [FinanceReportController::class, 'exportPdf']);
        Route::get('laporan/export-excel', [FinanceReportController::class, 'exportExcel']);
    });

    // Aset
    Route::prefix('aset')->name('asset.')->group(function () {
        Route::resource('inventaris', AssetController::class);
        Route::get('inventaris/{asset}/qr', [AssetController::class, 'generateQr']);
        Route::resource('peminjaman', AssetLoanController::class);
        Route::post('peminjaman/{loan}/approve', [AssetLoanController::class, 'approve']);
        Route::post('peminjaman/{loan}/return', [AssetLoanController::class, 'returnAsset']);
        Route::resource('maintenance', MaintenanceController::class);
    });

    // Kegiatan
    Route::prefix('kegiatan')->name('activity.')->group(function () {
        Route::resource('/', ActivityController::class);
        Route::get('kalender', [ActivityController::class, 'calendar'])->name('calendar');
        Route::get('{activity}/presensi', [AttendanceController::class, 'show'])->name('attendance');
        Route::post('{activity}/presensi', [AttendanceController::class, 'store']);
        Route::get('{activity}/qr', [ActivityController::class, 'qrCode'])->name('qr');
    });

    // Shalat & Imam
    Route::prefix('shalat')->name('prayer.')->group(function () {
        Route::get('jadwal', [PrayerScheduleController::class, 'index'])->name('schedule');
        Route::post('jadwal/generate', [PrayerScheduleController::class, 'generate']);
        Route::resource('imam', ImamController::class);
        Route::get('jadwal-imam', [ImamScheduleController::class, 'index'])->name('imam-schedule');
        Route::post('jadwal-imam', [ImamScheduleController::class, 'store']);
        Route::post('jadwal-imam/{schedule}/ganti', [ImamScheduleController::class, 'substitute']);
    });

    // Kajian
    Route::prefix('kajian')->name('study.')->group(function () {
        Route::resource('sesi', StudySessionController::class);
        Route::resource('majelis', MajelisController::class);
        Route::resource('majelis/{majelis}/anggota', MajelisAnggotaController::class);
    });

    // TPQ
    Route::prefix('tpq')->name('tpq.')->group(function () {
        Route::get('/', [TpqDashboardController::class, 'index'])->name('dashboard');
        Route::resource('pengaturan', TpqSettingController::class)->only(['edit', 'update']);
        Route::resource('tahun-ajaran', TpqAcademicYearController::class);
        Route::resource('semester', TpqSemesterController::class);
        Route::resource('kelas', TpqClassController::class);
        Route::resource('santri', TpqStudentController::class);
        Route::get('santri/{student}/kartu', [TpqStudentController::class, 'card']);
        Route::prefix('absensi')->name('attendance.')->group(function () {
            Route::get('/', [TpqAttendanceController::class, 'index'])->name('index');
            Route::get('{class}', [TpqAttendanceController::class, 'show'])->name('show');
            Route::post('{class}', [TpqAttendanceController::class, 'store'])->name('store');
            Route::get('{class}/rekap', [TpqAttendanceController::class, 'recap'])->name('recap');
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
            Route::post('{semester}/generate', [TpqReportCardController::class, 'generate'])->name('generate');
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
        Route::resource('sertifikat', TpqCertificateController::class);
    });

    // Jamaah
    Route::prefix('jamaah')->name('jamaah.')->group(function () {
        Route::resource('/', JamaahController::class);
        Route::resource('program-sosial', SocialProgramController::class);
        Route::get('broadcast', [BroadcastController::class, 'index'])->name('broadcast');
        Route::post('broadcast', [BroadcastController::class, 'send']);
    });

    // Ramadhan
    Route::prefix('ramadhan')->name('ramadhan.')->group(function () {
        Route::get('/', [RamadhanController::class, 'index'])->name('index');
        Route::post('imsakiyah/generate', [RamadhanController::class, 'generateImsakiyah']);
        Route::resource('khatam', KhatamTrackerController::class);
        Route::resource('itikaf', ItikafController::class);
        Route::resource('qurban', QurbanController::class);
    });

    // Wakaf & Pembangunan
    Route::prefix('wakaf')->name('wakaf.')->group(function () {
        Route::resource('/', WakafController::class);
        Route::resource('proyek', BuildingProjectController::class);
    });

    // Laporan
    Route::prefix('laporan')->name('report.')->group(function () {
        Route::get('keuangan', [ReportController::class, 'finance'])->name('finance');
        Route::get('aset', [ReportController::class, 'asset'])->name('asset');
        Route::get('kegiatan', [ReportController::class, 'activity'])->name('activity');
        Route::get('jamaah', [ReportController::class, 'jamaah'])->name('jamaah');
        Route::get('lpj', [ReportController::class, 'lpj'])->name('lpj');
        Route::post('lpj/generate', [ReportController::class, 'generateLpj']);
    });

    // Pengaturan
    Route::prefix('pengaturan')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('masjid', [SettingController::class, 'updateMasjid'])->name('masjid');
        Route::post('integrasi', [SettingController::class, 'updateIntegration'])->name('integration');
        Route::resource('pengguna', UserController::class);
        Route::resource('role', RoleController::class);
    });

    // Pengumuman
    Route::resource('pengumuman', AnnouncementController::class);

    // API internal
    Route::get('api/prayer-times/{date?}', [PrayerScheduleController::class, 'apiGet']);
    Route::post('api/attendance-qr/{activity}', [AttendanceController::class, 'scanQr']);
    Route::post('api/tpq-attendance-qr/{class}', [TpqAttendanceController::class, 'scanQr']);
});

// === PAYMENT WEBHOOK ===
Route::post('/webhook/midtrans', [PaymentController::class, 'midtransWebhook'])->name('webhook.midtrans');
```

---

## 7. MODUL 1 — DASHBOARD

### Dashboard Admin (`Pages/Dashboard/Index.vue`)

Layout: 4 stat card di atas → grafik keuangan 12 bulan → 3 kolom (kegiatan mendatang | aset perlu perhatian | donasi terbaru)

**Stat Cards (4 item, responsive grid 1→2→4):**
- 💰 Saldo Kas Total (semua akun)
- 📈 Pemasukan Bulan Ini
- 📉 Pengeluaran Bulan Ini
- 👥 Santri TPQ Aktif

**Grafik:** Line chart pemasukan vs pengeluaran 12 bulan terakhir (Chart.js, responsive)

**Widget Kegiatan Mendatang:** card list 3 item terdekat dengan countdown hari

**Widget Aset Perhatian:** daftar aset maintenance < 7 hari atau status rusak

**Widget Donasi Real-time:** 5 donasi terbaru hari ini dengan WebSocket (Reverb)

**Quick Actions:** 4 tombol: + Transaksi, + Kegiatan, 📢 Pengumuman, 🖨 Laporan

**DashboardController:**
```php
public function index(): Response
{
    return Inertia::render('Dashboard/Index', [
        'stats' => [
            'total_balance'     => KasAccount::totalBalance(),
            'income_this_month' => Transaction::incomeThisMonth(),
            'expense_this_month'=> Transaction::expenseThisMonth(),
            'tpq_students'      => TpqStudent::active()->count(),
        ],
        'chart_data'         => Transaction::monthlyChart(12),
        'upcoming_activities'=> Activity::upcoming(3)->get(),
        'maintenance_alerts' => Asset::needsMaintenance()->limit(5)->get(),
        'recent_donations'   => Donation::today()->latest()->limit(5)->get(),
    ]);
}
```

---

## 8. MODUL 2 — KEUANGAN

### 8.1 Transaksi (`Pages/Finance/Transactions/`)

**Index.vue:**
- Filter: tipe (pemasukan/pengeluaran), kategori, rekening, tanggal range, status
- Tabel: no.ref | tanggal | keterangan | kategori | rekening | nominal | status | aksi
- Total pemasukan & pengeluaran periode terpilih (sticky di atas tabel)
- Tombol: + Transaksi, Export PDF, Export Excel
- Mobile: card list (bukan tabel), swipe-to-action

**Form (Create/Edit):**
- Toggle: Pemasukan / Pengeluaran (tab, ubah warna form)
- Field: Tanggal, Rekening Kas, Kategori, Nominal (format otomatis Rp), Keterangan, Upload Bukti
- Submit → status `pending` jika user bukan bendahara

**Approval (untuk Bendahara):**
- Tab "Menunggu Approval" di halaman transaksi
- Tombol Setuju / Tolak dengan alasan

### 8.2 Laporan Keuangan (`Pages/Finance/Report/`)

**Tampilan filter:** Periode (custom range atau preset: minggu ini, bulan ini, tahun ini)

**Section yang ditampilkan:**
1. Ringkasan: total pemasukan, pengeluaran, saldo, perubahan vs periode lalu (%)
2. Breakdown per kategori (donut chart + tabel)
3. Tabel transaksi detail
4. QR Code laporan (link ke portal publik)

**PDF Export:** gunakan DomPDF, template `resources/views/pdf/financial-report.blade.php`
- Header: logo + nama masjid + periode + tanggal cetak
- Tabel transaksi
- Ringkasan saldo
- Tanda tangan digital pengurus

### 8.3 Donasi Digital

**Flow donasi publik:**
1. Jamaah buka `/donasi` (tidak perlu login)
2. Pilih nominal (preset: 10rb, 25rb, 50rb, 100rb, atau bebas)
3. Pilih tujuan (Umum, Renovasi, Sosial, dll)
4. Input nama (opsional, bisa anonim) + no HP
5. Pilih metode: QRIS / Transfer Bank
6. Redirect ke halaman pembayaran Midtrans
7. Setelah bayar → webhook → otomatis catat ke transaksi + kirim WA ucapan terima kasih

**Midtrans Integration (`app/Services/PaymentService.php`):**
```php
public function createDonationCharge(Donation $donation): array
{
    \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
    \Midtrans\Config::$isProduction = config('services.midtrans.is_production');

    $params = [
        'transaction_details' => [
            'order_id'     => $donation->id,
            'gross_amount' => (int) $donation->amount,
        ],
        'customer_details' => [
            'first_name' => $donation->donor_name ?? 'Donatur',
            'phone'      => $donation->donor_phone ?? '',
        ],
        'item_details' => [[
            'id'       => 'DONASI',
            'price'    => (int) $donation->amount,
            'quantity' => 1,
            'name'     => 'Donasi Masjid - ' . ($donation->purpose ?? 'Umum'),
        ]],
        'enabled_payments' => ['qris', 'bca_va', 'bni_va', 'bri_va'],
    ];

    return \Midtrans\Snap::createTransaction($params);
}
```

---

## 9. MODUL 3 — ASET

### 9.1 Inventaris (`Pages/Asset/Index.vue`)

**Tampilan:** Toggle antara Card Grid dan Tabel List

**Card Grid (default):**
- Foto aset (dari media library) atau placeholder ikon kategori
- Nama, kode, kondisi badge, lokasi
- Status badge (aktif/dipinjam/perbaikan)
- Tombol: Detail, QR Code

**Filter:** Kategori, Kondisi, Status, Cari nama/kode

**Form Aset:**
- Upload foto multiple (drag & drop)
- Auto-generate kode aset: `{kategori}/{tahun}/{urutan}` — contoh: `ELK/2025/001`
- Tombol "Generate QR Code" → buat QR yang link ke halaman detail aset publik

**QR Code Label (cetak):**
```php
// AssetController::generateQr()
$qr = QrCode::size(200)->generate(route('public.asset', $asset->asset_code));
// tampilkan di halaman cetak: nama aset, kode, QR, nama masjid
```

### 9.2 Maintenance Alert

**Command `SendMaintenanceReminders.php`** — jalankan setiap hari via scheduler:
```php
// Cek aset yang next_maintenance_date <= 7 hari dari sekarang
// Kirim WA ke admin pengurus aset
// Update status notif agar tidak kirim ulang
```

---

## 10. MODUL 4 — KEGIATAN

### 10.1 Kalender (`Pages/Activity/Calendar.vue`)

Gunakan **FullCalendar Vue 3** dengan:
- View: `dayGridMonth` (default), `timeGridWeek`, `listWeek`
- Warna event per kategori kegiatan
- Klik event → popup detail (nama, lokasi, PIC, tombol daftar/hadir)
- Tombol + di sudut untuk tambah kegiatan
- Mobile: default ke `listWeek`

### 10.2 Presensi QR

**Generate QR per kegiatan:**
```php
// QR berisi: simasjid.test/hadir/{activity_id}/{token}
// Token: hash(activity_id + secret)
// Jamaah scan → input nama + no HP → tercatat hadir
```

**Halaman Presensi Admin (`Pages/Activity/Attendance.vue`):**
- Daftar pendaftar + status hadir/belum
- Toggle manual hadir/tidak hadir
- Live counter: X hadir dari Y pendaftar (update via polling setiap 30 detik)
- Export daftar hadir (PDF)

---

## 11. MODUL 5 — SHALAT & IMAM

### 11.1 Kalkulasi Waktu Shalat

```php
// app/Services/PrayerTimeService.php
use IslamicNetwork\PrayerTimes\PrayerTimes;
use IslamicNetwork\PrayerTimes\Method;

public function calculate(float $lat, float $lng, Carbon $date): array
{
    // Gunakan package: composer require islamic-network/prayer-times
    $prayerTimes = new PrayerTimes(Method::MINISTRY_OF_RELIGIOUS_AFFAIRS_INDONESIA);
    $times = $prayerTimes->getTimes($date->toDateTime(), $lat, $lng);
    return [
        'fajr'    => $times->getFajr(),
        'sunrise' => $times->getSunrise(),
        'dhuhr'   => $times->getDhuhr(),
        'asr'     => $times->getAsr(),
        'maghrib' => $times->getMaghrib(),
        'isha'    => $times->getIsha(),
    ];
}
```

**Command `GeneratePrayerSchedule.php`** — jalankan setiap malam pukul 00.01:
```php
// Generate jadwal shalat 30 hari ke depan jika belum ada
// Cache juga di Redis untuk akses cepat portal publik
```

### 11.2 Jam Digital (`Pages/Prayer/DigitalClock.vue`)

Halaman `/jam-digital` — mode fullscreen untuk TV/proyektor:

```vue
<template>
  <div class="min-h-screen bg-gradient-to-b from-emerald-950 to-slate-950 flex flex-col">
    <!-- Header: Nama Masjid -->
    <div class="text-center pt-6 text-gold-400 font-arabic text-2xl">{{ masjidName }}</div>

    <!-- Jam Digital Besar -->
    <div class="flex-1 flex flex-col items-center justify-center gap-4">
      <div class="text-[8vw] font-bold text-white tracking-widest tabular-nums">{{ currentTime }}</div>
      <div class="text-2xl text-emerald-300">{{ hijriDate }} | {{ miladi }}</div>
    </div>

    <!-- Grid Waktu Shalat -->
    <div class="grid grid-cols-6 gap-2 p-6">
      <!-- Subuh | Dzuhur | Ashar | Maghrib | Isya | Imsak (Ramadhan) -->
      <!-- Active prayer: highlighted dengan glow effect -->
    </div>

    <!-- Ticker Berjalan -->
    <div class="bg-emerald-900/50 py-2 overflow-hidden">
      <div class="animate-marquee whitespace-nowrap text-emerald-200">
        {{ tickerText }}
      </div>
    </div>
  </div>
</template>

<script setup>
// Update jam setiap detik dengan setInterval
// Konversi ke Hijriah menggunakan moment-hijri
// Deteksi waktu shalat aktif
// Ticker: rotasi antara pengumuman + ayat Al-Quran + hadits harian
// WebSocket listen untuk update real-time ticker dari admin
</script>
```

### 11.3 Jadwal Imam (`Pages/Prayer/ImamSchedule.vue`)

**Tampilan bulanan:** tabel hari × shalat (Subuh | Dzuhur | Ashar | Maghrib | Isya | Jumat)

- Cell berisi nama imam
- Klik cell → edit imam / tambah pengganti
- Indikator: warna berbeda untuk imam tetap vs cadangan vs tamu
- Export PDF jadwal bulanan (siap tempel di masjid)
- Tombol "Kirim Notifikasi ke Semua Imam" → broadcast WA jadwal masing-masing

**Notifikasi WA ke Imam (H-1):**
```
Assalamu'alaikum Ust. [Nama],

Mengingatkan jadwal imam besok:
📅 [Hari], [Tanggal]
🕌 Shalat: [Nama Shalat] | Waktu: [Jam]
🏛️ [Nama Masjid]

Jazakallahu khairan.
```

---

## 12. MODUL 6 — KAJIAN & TPQ

### 12.1 TPQ Dashboard (`Pages/Learning/Tpq/Dashboard.vue`)

Stat: Total Santri | Hadir Hari Ini | SPP Outstanding | Raport Pending

Quick Links: Absensi Hari Ini | Input Nilai | Kelola Raport | SPP

### 12.2 Absensi Santri (`Pages/Learning/Tpq/Attendance/`)

**Index.vue:** pilih kelas + tanggal → tampil daftar santri

**AttendanceGrid.vue (komponen utama):**
```vue
<!--
Tampilan: tabel santri vs status kehadiran
- Kolom: No | Foto+Nama | Status (4 radio button bergambar) | Keterangan
- Status radio: ✅ Hadir (hijau) | 📋 Izin (biru) | 🤒 Sakit (kuning) | ❌ Alfa (merah)
- Mobile: setiap santri jadi card, status jadi 4 tombol besar (touch-friendly)
- Auto-save setelah 1 detik tidak ketik (debounce)
- Progress bar: X dari Y santri sudah diisi
- Tombol "Hadir Semua" untuk isi cepat
- Simpan → toast sukses + update stat hadir hari ini
-->
```

**Rekap Kehadiran (`Attendance/Recap.vue`):**
- Filter: kelas, periode (bulan/semester)
- Tabel santri × hari (calendar heat map style)
- Warna cell: hijau=hadir, merah=alfa, kuning=izin/sakit, abu=libur
- Kolom summary: total hadir, %, ranking kehadiran
- Export PDF laporan kehadiran per kelas per bulan

### 12.3 Input Nilai (`Pages/Learning/Tpq/Grades/`)

**GradeInput.vue:**
```vue
<!--
Layout: Tab per mapel di atas, tabel santri di bawah
Setiap baris santri:
- Nama santri
- Input nilai (number, 0-100) atau dropdown A/B/C/D
- Textarea deskripsi/narasi (collapsible)
- Indikator warna: hijau ≥ KKM, merah < KKM

Mobile: accordion per santri, buka → tampil semua mapel + input nilai

Auto-calculate nilai rata-rata saat semua mapel diisi
Simpan semua → satu tombol submit
-->
```

### 12.4 Progress Hafalan (`Components/Tpq/HafalanTracker.vue`)

```vue
<!--
Tampilan: grid 114 surah (card kecil)
Setiap card: nama surah + jumlah ayat + status (warna)
- Putih/abu: Belum
- Kuning: Sedang dihafal
- Hijau: Sudah hafal ✓ + tanggal

Klik surah → modal: input jumlah ayat yang sudah dihafal, checkbox "Sudah hafal semua", tombol verifikasi

Stats di atas: X surah hafal | X sedang | Estimasi selesai 1 juz
-->
```

### 12.5 Raport Semester (`Pages/Learning/Tpq/ReportCards/`)

**Index.vue (per semester):**
- Daftar semua santri + status raport (Belum dibuat | Sudah dibuat | Sudah dikirim WA)
- Filter: kelas, status
- Tombol: Generate Satu | Generate Semua Kelas Ini | Download ZIP | Kirim WA Semua

**Generate raport (`TpqReportCardService.php`):**
```php
public function generate(TpqStudent $student, TpqSemester $semester): TpqReportCard
{
    // 1. Ambil semua nilai per mapel semester ini
    // 2. Hitung rata-rata tertimbang
    // 3. Hitung rekap kehadiran (hadir/sakit/izin/alfa)
    // 4. Ambil progress hafalan terbaru
    // 5. Ambil catatan wali kelas
    // 6. Tentukan status kenaikan kelas (sesuai kriteria di tpq_settings)
    // 7. Simpan ke tpq_report_cards
    // 8. Generate PDF via DomPDF
    // 9. Simpan PDF ke storage
    // 10. Return record
}
```

**Template Raport PDF (`resources/views/pdf/report-card.blade.php`):**
```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    /* Font Arab untuk Al-Quran */
    @font-face { font-family: 'Amiri'; ... }
    
    body { font-family: 'Plus Jakarta Sans', Arial, sans-serif; font-size: 11pt; }
    .header { text-align: center; border-bottom: 2px solid #16a34a; padding-bottom: 12px; }
    .logo { width: 70px; height: 70px; }
    .school-name { font-size: 16pt; font-weight: bold; color: #16a34a; }
    .raport-title { font-size: 14pt; margin: 8px 0; }
    
    /* Grid nilai */
    table.grades { width: 100%; border-collapse: collapse; }
    table.grades th, table.grades td { border: 1px solid #ccc; padding: 6px 10px; }
    table.grades th { background: #f0fdf4; }
    
    /* Ornamen islami di sudut (bisa berupa border pattern) */
    .ornament-border { border: 3px double #16a34a; padding: 20px; }
    
    /* Status naik kelas */
    .promotion-status { font-size: 14pt; font-weight: bold; text-align: center;
                        border: 2px solid; padding: 8px; margin: 12px 0; }
    .naik  { color: #16a34a; border-color: #16a34a; }
    .tinggal { color: #dc2626; border-color: #dc2626; }
    .lulus { color: #d97706; border-color: #d97706; }
    
    /* Tanda tangan */
    .signature-area { display: flex; justify-content: space-between; margin-top: 30px; }
    .signature-box { text-align: center; width: 180px; }
    .signature-line { border-bottom: 1px solid black; margin: 40px 0 4px; }
  </style>
</head>
<body>
<div class="ornament-border">
  <!-- HEADER -->
  <div class="header">
    <img src="{{ $tpqLogo }}" class="logo">
    <div class="school-name">{{ $tpqName }}</div>
    <div>{{ $masjidName }} | {{ $masjidAddress }}</div>
    <div class="raport-title">RAPORT SEMESTER {{ $semester->number }} — TAHUN AJARAN {{ $academicYear }}</div>
  </div>

  <!-- IDENTITAS SANTRI -->
  <table style="width:100%; margin: 12px 0;">
    <tr>
      <td style="width:70%">
        <table>
          <tr><td width="140">Nama Santri</td><td>: <strong>{{ $student->name }}</strong></td></tr>
          <tr><td>NIS</td><td>: {{ $student->nis }}</td></tr>
          <tr><td>Kelas</td><td>: {{ $class->name }}</td></tr>
          <tr><td>Wali Kelas</td><td>: {{ $homeroomTeacher }}</td></tr>
          <tr><td>Tahun Ajaran</td><td>: {{ $academicYear }}</td></tr>
        </table>
      </td>
      <td style="text-align:right">
        @if($student->photo)
          <img src="{{ $student->photo }}" style="width:80px; height:100px; object-fit:cover; border:1px solid #ccc;">
        @endif
      </td>
    </tr>
  </table>

  <!-- NILAI PER MAPEL -->
  <table class="grades">
    <thead>
      <tr>
        <th>No</th>
        <th>Mata Pelajaran</th>
        <th>Nilai</th>
        <th>Predikat</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
      @foreach($grades as $i => $grade)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $grade->subject->name }}</td>
        <td style="text-align:center; font-weight:bold">{{ $grade->score }}</td>
        <td style="text-align:center">{{ $grade->grade_letter }}</td>
        <td>{{ $grade->description }}</td>
      </tr>
      @endforeach
      <tr style="background:#f0fdf4; font-weight:bold">
        <td colspan="2">Rata-Rata</td>
        <td style="text-align:center">{{ number_format($reportCard->average_score, 1) }}</td>
        <td colspan="2"></td>
      </tr>
    </tbody>
  </table>

  <!-- KEHADIRAN -->
  <table class="grades" style="margin-top:12px">
    <thead>
      <tr><th colspan="4" style="text-align:left">Rekap Kehadiran</th></tr>
      <tr><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alfa</th></tr>
    </thead>
    <tbody>
      <tr>
        <td style="text-align:center">{{ $reportCard->present_count }} hari</td>
        <td style="text-align:center">{{ $reportCard->sick_count }} hari</td>
        <td style="text-align:center">{{ $reportCard->permission_count }} hari</td>
        <td style="text-align:center">{{ $reportCard->absent_count }} hari</td>
      </tr>
    </tbody>
  </table>

  <!-- HAFALAN -->
  <div style="margin-top:12px; padding:8px; background:#f0fdf4; border-radius:4px">
    <strong>Pencapaian Hafalan Semester Ini:</strong>
    <div>{{ $hafalanSummary }}</div>
  </div>

  <!-- CATATAN WALI KELAS -->
  <div style="margin-top:12px">
    <strong>Catatan Wali Kelas:</strong>
    <div style="border:1px solid #ccc; padding:8px; min-height:50px; margin-top:4px">
      {{ $reportCard->homeroom_notes ?? '-' }}
    </div>
  </div>

  <!-- CATATAN KEPALA TPQ -->
  <div style="margin-top:8px">
    <strong>Catatan Kepala TPQ:</strong>
    <div style="border:1px solid #ccc; padding:8px; min-height:40px; margin-top:4px">
      {{ $reportCard->head_notes ?? '-' }}
    </div>
  </div>

  <!-- STATUS KENAIKAN KELAS -->
  <div class="promotion-status {{ $reportCard->promotion_status }}">
    @if($reportCard->promotion_status === 'naik')
      ✓ DINYATAKAN NAIK KE KELAS {{ $nextClass }}
    @elseif($reportCard->promotion_status === 'tinggal')
      ✗ DINYATAKAN TINGGAL KELAS
    @else
      ★ DINYATAKAN LULUS / KHATAM
    @endif
  </div>

  <!-- TANDA TANGAN -->
  <div class="signature-area">
    <div class="signature-box">
      <div>Wali Murid</div>
      <div class="signature-line"></div>
      <div>( __________________ )</div>
    </div>
    <div class="signature-box">
      <div>{{ $masjidCity }}, {{ now()->translatedFormat('d F Y') }}</div>
      <div>Wali Kelas</div>
      @if($homeroomSignature)
        <img src="{{ $homeroomSignature }}" style="height:50px; margin: 4px auto;">
      @else
        <div style="height:50px"></div>
      @endif
      <div class="signature-line"></div>
      <div>{{ $homeroomTeacher }}</div>
    </div>
    <div class="signature-box">
      <div>Kepala TPQ</div>
      @if($headSignature)
        <img src="{{ $headSignature }}" style="height:50px; margin: 4px auto;">
      @else
        <div style="height:50px"></div>
      @endif
      <div class="signature-line"></div>
      <div>{{ $tpqHeadName }}</div>
    </div>
  </div>
</div>
</body>
</html>
```

**Kirim Raport via WhatsApp:**
```php
// TpqReportCardController::sendWhatsApp()
$message = "Assalamu'alaikum Bapak/Ibu Wali Murid *{$student->father_name}*,\n\n"
    . "Raport semester {$semester->number} tahun ajaran {$academicYear} "
    . "untuk ananda *{$student->name}* sudah tersedia.\n\n"
    . "📊 Nilai Rata-rata: *{$reportCard->average_score}*\n"
    . "✅ Kehadiran: *{$reportCard->present_count} hari* ({$attendancePercent}%)\n"
    . "📈 Status: *" . strtoupper($reportCard->promotion_status) . "*\n\n"
    . "Silakan download raport di link berikut:\n"
    . route('wali.reportcard', $reportCard->id) . "\n\n"
    . "Jazakallahu khairan 🙏\n"
    . "*TPQ " . $tpqName . "*";

// Kirim PDF attachment via Fonnte
WhatsAppService::sendDocument($student->guardian_whatsapp, $message, $reportCard->pdf_path);
```

### 12.6 SPP TPQ (`Pages/Learning/Tpq/Spp/`)

**Index.vue:** tampilan per bulan

- Header: Bulan [dropdown] Tahun [dropdown]
- Summary: X lunas, Y belum bayar, Total outstanding Rp XX
- Tabel: Nama | Kelas | Nominal | Status | Tgl Bayar | Aksi (Catat Bayar | WA Reminder)
- Tombol: Generate Tagihan Bulan Ini, Kirim Reminder Semua Belum Bayar

**Status badge:**
- 🟢 Lunas
- 🔴 Belum Bayar
- 🟡 Cicil

---

## 13. MODUL 7 — JAMAAH & SOSIAL

### 13.1 Database Jamaah (`Pages/Jamaah/Index.vue`)

- Tabel dengan avatar + nama + RT/RW + status + tag + tombol
- Filter: status, RT/RW, tag
- Import Excel (template download tersedia)
- Kartu jamaah digital (QR Code untuk scan absensi kegiatan)

### 13.2 Program Sosial

- Buat program: nama, deskripsi, anggaran, periode
- Tambah penerima dari database jamaah (atau tambah manual)
- Distribusi: input tanggal, jenis bantuan, jumlah per penerima
- Tanda terima digital (PDF + tanda tangan elektronik penerima)
- Laporan distribusi untuk donatur

### 13.3 Broadcast WhatsApp

```php
// BroadcastController::send()
// Target: semua jamaah aktif / per RT / per tag / manual input nomor
// Template tersedia, bisa custom
// Queue: BroadcastWhatsAppJob — kirim dengan delay 2 detik antar pesan (hindari spam)
// Progress tracker: X/Y pesan terkirim (real-time via Reverb)
```

---

## 14. MODUL 8 — RAMADHAN & PHBI

### 14.1 Mode Ramadhan

Deteksi otomatis jika bulan Ramadhan (Hijriah):
- Dashboard muncul widget "Mode Ramadhan"
- Imsakiyah otomatis dari PrayerTimeService (tambahkan imsak = subuh - 10 menit)
- Jam digital ganti tema ke Ramadhan (bulan sabit + bintang, warna navy-gold)
- Ticker auto-include "Selamat Menunaikan Ibadah Puasa"

### 14.2 Qurban (`Pages/Ramadhan/Qurban/`)

**Pendaftaran:**
- Form: nama shohibul qurban, no HP, jenis hewan (sapi 1/7, kambing), nama hewan (opsional)
- Jumlah shohibul qurban realtime counter
- Export daftar shohibul qurban (PDF)

**Distribusi:**
- Input penerima daging (bisa ambil dari database jamaah)
- Input berat per paket per penerima
- Cetak label distribusi (nama + alamat penerima)
- Laporan distribusi: total kg dibagikan, jumlah penerima, sisa

---

## 15. MODUL 9 — WAKAF & PEMBANGUNAN

### 15.1 Proyek (`Pages/Wakaf/Projects/`)

- Card proyek dengan progress bar (% fisik + % dana)
- Detail: RAB, realisasi, foto progress timeline
- Galeri foto weekly update
- Share link progress untuk donatur

---

## 16. MODUL 10 — LAPORAN & TRANSPARANSI

### 16.1 Laporan LPJ Tahunan

```php
// ReportController::generateLpj()
// Generate PDF laporan pertanggungjawaban DKM tahunan
// Isi:
// Cover: nama masjid, logo, periode, foto masjid
// Bab 1: Kata pengantar Ketua DKM
// Bab 2: Susunan pengurus
// Bab 3: Laporan keuangan (neraca, arus kas)
// Bab 4: Program dan kegiatan yang terlaksana
// Bab 5: Laporan aset
// Bab 6: Laporan TPQ
// Bab 7: Program sosial
// Bab 8: Penutup dan rencana ke depan
```

### 16.2 Audit Trail

```php
// Gunakan spatie/laravel-activitylog
// Log otomatis setiap create/update/delete di semua model penting
// Tampilan di Settings > Log Aktivitas
// Filter: user, model, aksi, tanggal
```

---

## 17. PORTAL PUBLIK JAMAAH

### `Pages/Public/Portal.vue`

```vue
<!--
URL: / (root)
Tidak perlu login

SECTION 1 — HERO:
- Background: foto masjid + overlay hijau gelap
- DigitalClock.vue (jam, tanggal Hijriah, Masehi)
- PrayerCountdown.vue (countdown waktu shalat berikutnya)
- Dark/Light mode toggle (pojok kanan atas)

SECTION 2 — JADWAL SHALAT HARI INI:
- 6 card: Subuh | Terbit | Dzuhur | Ashar | Maghrib | Isya
- Card aktif: highlighted warna hijau + animasi pulse
- Responsive: 3 kolom mobile, 6 kolom desktop

SECTION 3 — PENGUMUMAN TERBARU:
- 3 card pengumuman terbaru
- Badge tipe (Umum/Urgent/Kegiatan)
- Tombol "Lihat Semua"

SECTION 4 — KEGIATAN MENDATANG:
- List 5 kegiatan terdekat
- Ikon kategori, nama, tanggal, lokasi
- Tombol daftar (jika ada pendaftaran)

SECTION 5 — JADWAL IMAM PEKAN INI:
- Tabel 7 hari × 6 waktu shalat
- Nama imam per cell
- Highlight hari ini
- Link "Lihat Jadwal Lengkap"

SECTION 6 — DONASI DIGITAL:
- Tombol besar "💚 Donasi / Infaq Sekarang"
- Info rekening resmi
- Total donasi bulan ini (counter animasi)
- 5 donatur terbaru (anonim atau nama tertera)

SECTION 7 — TRANSPARANSI KEUANGAN:
- Ringkasan keuangan bulan berjalan: Pemasukan | Pengeluaran | Saldo
- Tombol "Lihat Laporan Lengkap"

SECTION 8 — INFO MASJID:
- Alamat + Google Maps embed
- Kontak (WhatsApp admin)
- Sosial media links
- Footer: copyright + nama masjid
-->
```

### DigitalClock.vue

```vue
<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import moment from 'moment-hijri'

const time = ref('')
const hijriDate = ref('')
const miladiDate = ref('')
const nextPrayer = ref(null)
const countdown = ref('')

let timer
onMounted(() => {
  timer = setInterval(() => {
    const now = moment()
    time.value = now.format('HH:mm:ss')
    miladiDate.value = now.format('dddd, DD MMMM YYYY')
    hijriDate.value = now.format('iD iMMMM iYYYY H')  // Hijriah
    updateCountdown(now)
  }, 1000)
})
onUnmounted(() => clearInterval(timer))

function updateCountdown(now) {
  // Bandingkan dengan props.prayerTimes untuk cari waktu shalat berikutnya
  // Hitung selisih dan format HH:MM:SS
}
</script>
```

---

## 18. INTEGRASI EKSTERNAL

### 18.1 WhatsApp via Fonnte (`app/Services/WhatsAppService.php`)

```php
class WhatsAppService
{
    private string $token;
    private string $baseUrl = 'https://api.fonnte.com';

    public function __construct()
    {
        $this->token = config('services.fonnte.token');
    }

    public function send(string $phone, string $message): bool
    {
        $response = Http::withHeaders(['Authorization' => $this->token])
            ->post("{$this->baseUrl}/send", [
                'target'  => $phone,
                'message' => $message,
            ]);
        return $response->successful();
    }

    public function sendDocument(string $phone, string $message, string $filePath): bool
    {
        // Upload file + kirim sebagai dokumen
        $response = Http::withHeaders(['Authorization' => $this->token])
            ->attach('file', file_get_contents(storage_path("app/{$filePath}")), basename($filePath))
            ->post("{$this->baseUrl}/send", [
                'target'  => $phone,
                'message' => $message,
            ]);
        return $response->successful();
    }

    public function sendBulk(array $phones, string $message): void
    {
        foreach ($phones as $phone) {
            SendWhatsAppNotification::dispatch($phone, $message)->delay(now()->addSeconds(2));
        }
    }
}
```

### 18.2 Midtrans Payment

Sudah dijelaskan di Modul 2.3. Tambahan untuk webhook:

```php
// PaymentController::midtransWebhook()
public function midtransWebhook(Request $request): JsonResponse
{
    $notification = new \Midtrans\Notification();
    $orderId = $notification->order_id;
    $status  = $notification->transaction_status;

    $donation = Donation::find($orderId);
    if (!$donation) return response()->json(['status' => 'not_found'], 404);

    if (in_array($status, ['settlement', 'capture'])) {
        $donation->update(['status' => 'paid', 'paid_at' => now()]);
        // Catat sebagai transaksi pemasukan
        Transaction::createFromDonation($donation);
        // Kirim WA ucapan terima kasih
        SendDonationReceipt::dispatch($donation);
        // Broadcast real-time ke dashboard admin
        broadcast(new DonationReceived($donation));
    } elseif ($status === 'expire') {
        $donation->update(['status' => 'expired']);
    }

    return response()->json(['status' => 'ok']);
}
```

---

## 19. NOTIFIKASI & QUEUE

### 19.1 Scheduler (`app/Console/Kernel.php`)

```php
protected function schedule(Schedule $schedule): void
{
    // Setiap hari jam 00:01 — generate jadwal shalat 30 hari ke depan
    $schedule->command('simasjid:generate-prayer-schedule')->dailyAt('00:01');

    // Setiap hari jam 07:00 — cek maintenance H-7
    $schedule->command('simasjid:maintenance-reminders')->dailyAt('07:00');

    // Tanggal 1 setiap bulan jam 06:00 — generate tagihan SPP
    $schedule->command('simasjid:generate-spp-bills')->monthlyOn(1, '06:00');

    // Setiap hari jam 08:00 — kirim reminder SPP yang sudah lewat 7 hari
    $schedule->command('simasjid:spp-reminders')->dailyAt('08:00');

    // H-1 jam 20:00 — reminder imam besok
    $schedule->command('simasjid:imam-reminders')->dailyAt('20:00');

    // H-1 kegiatan jam 09:00 — reminder ke pendaftar
    $schedule->command('simasjid:activity-reminders')->dailyAt('09:00');
}
```

### 19.2 Jobs

```php
// Jobs yang tersedia:
// SendWhatsAppNotification::class  → kirim WA tunggal via Fonnte
// SendDonationReceipt::class       → WA ucapan terima kasih + bukti
// GenerateReportCard::class        → generate PDF raport (berat, masuk queue)
// SendReportCardWhatsApp::class    → kirim PDF raport ke wali murid
// BroadcastAnnouncementWA::class   → broadcast pengumuman ke jamaah (batch)

// Semua job masuk ke queue 'default'
// Job berat (PDF generate): queue 'heavy'
// Supervisor config: 3 worker 'default', 1 worker 'heavy'
```

### 19.3 WebSocket (Reverb)

```php
// Events yang di-broadcast:
// DonationReceived       → dashboard admin (widget donasi real-time)
// AnnouncementPublished  → portal publik (notif pengumuman baru)
// ActivityCheckIn        → halaman presensi (live counter)
// BroadcastProgress      → halaman broadcast WA (progress X/Y)
```

---

## 20. TESTING

```php
// Feature tests yang WAJIB dibuat:

// AuthTest: login, logout, unauthorized access
// TransactionTest: create, approve, reject, laporan filter
// DonationTest: create charge, webhook success, webhook expire
// AssetTest: create, QR generate, loan flow, maintenance alert
// TpqAttendanceTest: input absensi, recap calculation, alfa alert
// TpqGradeTest: input nilai, calculate average, KKM check
// TpqReportCardTest: generate report, PDF create, WA send
// TpqSppTest: generate bills, pay, scholarship flag, reminder
// PrayerScheduleTest: calculate times, generate 30 days, cache hit
// ImamScheduleTest: create, notify, substitute flow
// PublicPortalTest: unauthenticated access, correct data displayed

// php artisan test --filter TpqReportCardTest
```

---

## 21. DEPLOYMENT

### 21.1 Server Setup (Ubuntu 24.04)

```bash
# PHP 8.3 + extensions
sudo apt install php8.3 php8.3-{fpm,pgsql,redis,gd,zip,mbstring,xml,curl,intl}

# PostgreSQL 16
sudo apt install postgresql-16

# Redis
sudo apt install redis-server

# Nginx
sudo apt install nginx

# Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs
```

### 21.2 Nginx Config

```nginx
server {
    listen 80;
    server_name simasjid.yourdomain.com;
    root /var/www/simasjid/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }

    # WebSocket Reverb
    location /app {
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
}
```

### 21.3 Supervisor Config

```ini
; /etc/supervisor/conf.d/simasjid.conf

[program:simasjid-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/simasjid/artisan queue:work redis --queue=default --tries=3 --sleep=3
autostart=true
autorestart=true
numprocs=3
redirect_stderr=true
stdout_logfile=/var/www/simasjid/storage/logs/worker-default.log

[program:simasjid-worker-heavy]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/simasjid/artisan queue:work redis --queue=heavy --tries=2 --timeout=120
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/simasjid/storage/logs/worker-heavy.log

[program:simasjid-reverb]
process_name=%(program_name)s
command=php /var/www/simasjid/artisan reverb:start --host=127.0.0.1 --port=8080
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/simasjid/storage/logs/reverb.log
```

### 21.4 Deploy Commands

```bash
cd /var/www/simasjid

# Pull code
git pull origin main

# Dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Laravel
php artisan migrate --force
php artisan db:seed --class=TransactionCategorySeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

# Queue restart
php artisan queue:restart

# Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

# Permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache

# SSL
sudo certbot --nginx -d simasjid.yourdomain.com
```

### 21.5 Cron

```bash
# crontab -e
* * * * * cd /var/www/simasjid && php artisan schedule:run >> /dev/null 2>&1
```

---

## CHECKLIST IMPLEMENTASI

### Phase 1 — Foundation (Wajib selesai dulu)
- [ ] Project setup + semua package terinstall
- [ ] Semua migration dibuat dan dijalankan
- [ ] Seeder data awal
- [ ] Design system (Tailwind config, CSS variables, dark mode)
- [ ] AdminLayout + PublicLayout + komponen atom UI
- [ ] Auth (login, logout, middleware)
- [ ] Role & Permission (Spatie)
- [ ] Settings: profil masjid, manajemen user
- [ ] Dashboard admin (stat cards + grafik + widget)
- [ ] Jadwal shalat (kalkulasi + generate command + cache)
- [ ] Jam digital fullscreen
- [ ] Portal publik jamaah (halaman /  lengkap)

### Phase 2 — Core Modules
- [ ] Modul Keuangan lengkap (transaksi, kas, RAB, laporan, export)
- [ ] Donasi digital (Midtrans + webhook)
- [ ] Modul Aset (inventaris, QR, maintenance, peminjaman)
- [ ] Modul Kegiatan (kalender, presensi QR, publikasi)
- [ ] Jadwal imam (schedule, notifikasi WA)
- [ ] Pengumuman
- [ ] WhatsApp Service (Fonnte)

### Phase 3 — Extended Modules
- [ ] TPQ lengkap (data santri, absensi, nilai, hafalan, raport, SPP, sertifikat)
- [ ] Portal wali murid
- [ ] Kajian & Majelis Taklim
- [ ] Database Jamaah + Program Sosial
- [ ] Zakat & Wakaf
- [ ] Laporan LPJ tahunan

### Phase 4 — Polish & Production
- [ ] Modul Ramadhan + Qurban
- [ ] Wakaf & Proyek Pembangunan
- [ ] Perpustakaan
- [ ] Testing semua modul
- [ ] PWA (manifest + service worker untuk offline)
- [ ] Performance optimization (eager loading, index DB, cache)
- [ ] Deployment VPS + SSL + Supervisor
- [ ] Monitoring (Telescope, Horizon dashboard)

### Phase 5 — Flutter Android App
- [ ] Setup project Flutter + struktur folder
- [ ] API backend: auth mobile, FCM token, presensi, capaian, SPP
- [ ] FCM setup (firebase_messaging + Laravel)
- [ ] WebView hybrid wrapper selesai
- [ ] Screen Login native Flutter
- [ ] Screen Dashboard ustadz (stat + quick actions)
- [ ] Screen Presensi Santri (GPS validation + submit)
- [ ] Screen Capaian Santri (nilai + hafalan per santri)
- [ ] Push notif: presensi, capaian, SPP reminder
- [ ] Build APK release + signing
- [ ] Upload ke Google Play / distribusi APK langsung

---

## 22. FLUTTER ANDROID APP — Ustadz/Ustadzah

> **Konsep:** Hybrid app — native Flutter untuk fitur yang butuh hardware (GPS, push notif), WebView untuk halaman lain yang sudah ada di web. Ringan, tidak perlu rebuild UI dari nol, dan tetap bisa pakai semua fitur web.

---

### 22.1 Arsitektur Hybrid

```
┌─────────────────────────────────────────┐
│           FLUTTER APP (Android)          │
│                                          │
│  ┌─────────────┐   ┌──────────────────┐ │
│  │ Native Layer │   │  WebView Layer   │ │
│  │              │   │                  │ │
│  │ • Login      │   │ • Halaman web    │ │
│  │ • Dashboard  │   │   admin masjid   │ │
│  │ • Presensi   │   │ • Keuangan       │ │
│  │   (GPS)      │   │ • Kegiatan       │ │
│  │ • Capaian    │   │ • Laporan        │ │
│  │   Santri     │   │ • Pengaturan     │ │
│  │ • Push Notif │   │   (semua modul   │ │
│  │              │   │    web lainnya)  │ │
│  └──────┬───────┘   └────────┬─────────┘ │
│         │                    │            │
│         └────────┬───────────┘            │
│                  ▼                        │
│         Laravel REST API                  │
│         /api/mobile/v1/...               │
└─────────────────────────────────────────┘
```

**Alur kerja:**
- Screen native Flutter: Login, Dashboard, Presensi, Capaian Santri
- Semua screen lain → buka WebView dengan token sudah ter-inject (auto login)
- Push notif diterima native → tap notif → buka screen native atau WebView halaman terkait

---

### 22.2 Project Setup Flutter

```bash
# Buat project Flutter baru (terpisah dari Laravel)
flutter create simasjid_app --org com.simasjid --platforms android
cd simasjid_app

# Minimum SDK: Android 5.0 (API 21)
# Target SDK: Android 14 (API 34)
```

**`pubspec.yaml`:**
```yaml
name: simasjid_app
description: SiMasjid - Aplikasi Ustadz/Ustadzah TPQ

environment:
  sdk: '>=3.3.0 <4.0.0'
  flutter: '>=3.19.0'

dependencies:
  flutter:
    sdk: flutter

  # WebView
  webview_flutter: ^4.7.0
  webview_flutter_android: ^3.16.0

  # HTTP & API
  dio: ^5.4.3
  retrofit: ^4.1.0
  pretty_dio_logger: ^1.3.1

  # State Management
  flutter_riverpod: ^2.5.1
  riverpod_annotation: ^2.3.5

  # Local Storage
  flutter_secure_storage: ^9.0.0    # simpan token JWT
  shared_preferences: ^2.2.3

  # Push Notification (Firebase)
  firebase_core: ^2.30.1
  firebase_messaging: ^14.9.1
  flutter_local_notifications: ^17.1.2

  # Geolocation
  geolocator: ^11.0.0
  permission_handler: ^11.3.1
  geocoding: ^3.0.0

  # UI
  google_fonts: ^6.2.1
  flutter_svg: ^2.0.10
  cached_network_image: ^3.3.1
  shimmer: ^3.0.0
  lottie: ^3.1.0                    # animasi loading/sukses

  # Utils
  intl: ^0.19.0
  timeago: ^3.6.1
  url_launcher: ^6.2.6
  package_info_plus: ^8.0.0
  connectivity_plus: ^6.0.3
  flutter_dotenv: ^5.1.0

dev_dependencies:
  flutter_test:
    sdk: flutter
  build_runner: ^2.4.9
  retrofit_generator: ^8.1.0
  riverpod_generator: ^2.4.0
  flutter_lints: ^4.0.0
```

---

### 22.3 Struktur Folder Flutter

```
lib/
├── main.dart
├── firebase_options.dart           # generated by FlutterFire CLI
├── core/
│   ├── constants/
│   │   ├── app_colors.dart         # warna brand (sync dengan Tailwind)
│   │   ├── app_strings.dart        # semua string / teks UI
│   │   └── api_endpoints.dart      # semua URL endpoint
│   ├── network/
│   │   ├── dio_client.dart         # setup Dio + interceptor auth
│   │   ├── api_service.dart        # Retrofit API interface
│   │   └── api_result.dart         # wrapper response sukses/error
│   ├── storage/
│   │   └── secure_storage.dart     # wrapper flutter_secure_storage
│   ├── notifications/
│   │   ├── fcm_service.dart        # setup Firebase Messaging
│   │   └── local_notification_service.dart
│   ├── location/
│   │   └── location_service.dart   # wrapper geolocator
│   └── router/
│       └── app_router.dart         # GoRouter / Navigator 2.0
├── features/
│   ├── auth/
│   │   ├── data/
│   │   │   ├── auth_repository.dart
│   │   │   └── models/
│   │   │       ├── login_request.dart
│   │   │       └── user_model.dart
│   │   ├── providers/
│   │   │   └── auth_provider.dart
│   │   └── screens/
│   │       └── login_screen.dart
│   ├── dashboard/
│   │   ├── data/
│   │   │   ├── dashboard_repository.dart
│   │   │   └── models/dashboard_stats.dart
│   │   ├── providers/
│   │   │   └── dashboard_provider.dart
│   │   └── screens/
│   │       └── dashboard_screen.dart
│   ├── presensi/
│   │   ├── data/
│   │   │   ├── presensi_repository.dart
│   │   │   └── models/
│   │   │       ├── kelas_model.dart
│   │   │       ├── santri_model.dart
│   │   │       └── presensi_request.dart
│   │   ├── providers/
│   │   │   └── presensi_provider.dart
│   │   └── screens/
│   │       ├── presensi_kelas_screen.dart   # pilih kelas
│   │       ├── presensi_input_screen.dart   # input hadir/alfa per santri
│   │       └── presensi_rekap_screen.dart   # rekap kehadiran
│   ├── capaian/
│   │   ├── data/
│   │   │   ├── capaian_repository.dart
│   │   │   └── models/
│   │   │       ├── capaian_model.dart
│   │   │       └── hafalan_model.dart
│   │   ├── providers/
│   │   │   └── capaian_provider.dart
│   │   └── screens/
│   │       ├── capaian_kelas_screen.dart    # pilih kelas + santri
│   │       ├── capaian_detail_screen.dart   # detail nilai + hafalan satu santri
│   │       └── input_nilai_screen.dart      # input/update nilai
│   └── webview/
│       ├── providers/
│       │   └── webview_provider.dart
│       └── screens/
│           └── webview_screen.dart          # WebView wrapper dengan inject token
├── shared/
│   ├── widgets/
│   │   ├── app_button.dart
│   │   ├── app_text_field.dart
│   │   ├── app_loading.dart
│   │   ├── app_error_widget.dart
│   │   ├── stat_card.dart
│   │   ├── section_header.dart
│   │   └── bottom_nav_bar.dart
│   └── theme/
│       ├── app_theme.dart          # light + dark theme
│       └── app_text_styles.dart
└── l10n/
    └── app_id.arb                  # bahasa Indonesia
```

---

### 22.4 Backend — Laravel API Mobile

Tambahkan route group khusus mobile di `routes/api.php`:

```php
// routes/api.php

// === MOBILE API v1 ===
Route::prefix('mobile/v1')->name('mobile.')->group(function () {

    // Auth
    Route::post('login', [MobileAuthController::class, 'login']);
    Route::post('logout', [MobileAuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('fcm-token', [MobileAuthController::class, 'updateFcmToken'])->middleware('auth:sanctum');
    Route::get('profile', [MobileAuthController::class, 'profile'])->middleware('auth:sanctum');

    Route::middleware(['auth:sanctum', 'role:ustadz|admin|super_admin'])->group(function () {

        // Dashboard stats untuk ustadz
        Route::get('dashboard', [MobileDashboardController::class, 'index']);

        // Presensi
        Route::get('presensi/kelas', [MobilePresensiController::class, 'kelasList']);
        Route::get('presensi/kelas/{class}/santri', [MobilePresensiController::class, 'santriList']);
        Route::get('presensi/kelas/{class}/today', [MobilePresensiController::class, 'todayAttendance']);
        Route::post('presensi/kelas/{class}/submit', [MobilePresensiController::class, 'submit']);
        Route::get('presensi/rekap/{class}', [MobilePresensiController::class, 'rekap']);

        // Capaian santri
        Route::get('capaian/kelas/{class}/santri', [MobileCapaianController::class, 'santriList']);
        Route::get('capaian/santri/{student}', [MobileCapaianController::class, 'detail']);
        Route::get('capaian/santri/{student}/hafalan', [MobileCapaianController::class, 'hafalan']);
        Route::post('capaian/santri/{student}/nilai', [MobileCapaianController::class, 'inputNilai']);
        Route::post('capaian/santri/{student}/hafalan', [MobileCapaianController::class, 'updateHafalan']);

        // SPP (read-only untuk ustadz — hanya lihat status)
        Route::get('spp/kelas/{class}', [MobileSppController::class, 'kelasSpp']);
        Route::get('spp/santri/{student}', [MobileSppController::class, 'santriSpp']);

        // Notifikasi
        Route::get('notifications', [MobileNotificationController::class, 'index']);
        Route::post('notifications/{id}/read', [MobileNotificationController::class, 'markRead']);
        Route::post('notifications/read-all', [MobileNotificationController::class, 'markAllRead']);

        // WebView token inject
        Route::get('webview-token', [MobileAuthController::class, 'webviewToken']);
    });
});
```

**`MobileAuthController.php`:**
```php
class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone'     => 'required|string',
            'password'  => 'required|string',
            'fcm_token' => 'nullable|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Nomor HP atau password salah.'], 401);
        }

        if (!$user->hasRole(['ustadz', 'admin', 'super_admin'])) {
            return response()->json(['message' => 'Akses tidak diizinkan untuk role ini.'], 403);
        }

        // Simpan FCM token
        if ($request->fcm_token) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        // Revoke token lama, buat baru
        $user->tokens()->delete();
        $token = $user->createToken('mobile-app', ['mobile'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'     => $user->id,
                'name'   => $user->name,
                'phone'  => $user->phone,
                'avatar' => $user->avatar_url,
                'role'   => $user->getRoleNames()->first(),
                'masjid' => [
                    'id'   => $user->masjid->id,
                    'name' => $user->masjid->name,
                    'logo' => $user->masjid->logo_url,
                ],
            ],
        ]);
    }

    public function webviewToken(Request $request): JsonResponse
    {
        // Buat short-lived token (15 menit) untuk inject ke WebView
        // WebView akan auto-login menggunakan token ini
        $token = $request->user()->createToken('webview', ['webview'], now()->addMinutes(15))->plainTextToken;
        return response()->json(['token' => $token, 'url' => config('app.url')]);
    }
}
```

**`MobilePresensiController.php`:**
```php
class MobilePresensiController extends Controller
{
    public function submit(Request $request, TpqClass $class): JsonResponse
    {
        $request->validate([
            'date'       => 'required|date',
            'latitude'   => 'required|numeric|between:-90,90',
            'longitude'  => 'required|numeric|between:-180,180',
            'accuracy'   => 'required|numeric',
            'attendances'=> 'required|array|min:1',
            'attendances.*.student_id' => 'required|uuid|exists:tpq_students,id',
            'attendances.*.status'     => 'required|in:hadir,izin,sakit,alfa',
            'attendances.*.notes'      => 'nullable|string|max:200',
        ]);

        // Validasi geolocation: ustadz harus dalam radius 500m dari masjid
        $masjid   = $request->user()->masjid;
        $distance = $this->haversineDistance(
            $masjid->latitude, $masjid->longitude,
            $request->latitude, $request->longitude
        );

        if ($distance > 500) { // meter
            return response()->json([
                'message' => "Anda berada terlalu jauh dari masjid ({$distance}m). Presensi harus dilakukan di area masjid (maks. 500m).",
                'distance' => $distance,
            ], 422);
        }

        // Simpan semua kehadiran
        $saved = 0;
        foreach ($request->attendances as $item) {
            TpqAttendance::updateOrCreate(
                ['student_id' => $item['student_id'], 'date' => $request->date],
                [
                    'class_id'    => $class->id,
                    'status'      => $item['status'],
                    'notes'       => $item['notes'] ?? null,
                    'recorded_by' => $request->user()->id,
                    'latitude'    => $request->latitude,
                    'longitude'   => $request->longitude,
                ]
            );
            $saved++;
        }

        // Kirim push notif ke admin/kepala TPQ
        $this->notifyAdmin($class, $request->date, $saved, $request->user());

        // Kirim WA ke wali murid yang anaknya alfa (queue)
        $alfaStudents = collect($request->attendances)->where('status', 'alfa');
        foreach ($alfaStudents as $item) {
            SendAlfaAlertJob::dispatch($item['student_id'], $request->date)->onQueue('default');
        }

        return response()->json([
            'message' => "Presensi berhasil disimpan untuk {$saved} santri.",
            'saved'   => $saved,
            'date'    => $request->date,
        ]);
    }

    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371000; // radius bumi dalam meter
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lon2 - $lon1);
        $a  = sin($Δφ/2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ/2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
```

**Migration tambahan — kolom GPS di `tpq_attendances`:**
```php
// Tambahkan ke migration tpq_attendances yang sudah ada:
$table->decimal('latitude', 10, 7)->nullable();   // lokasi saat absen
$table->decimal('longitude', 10, 7)->nullable();
$table->string('device_info')->nullable();         // info perangkat ustadz
```

**FCM Push Notification Service (`app/Services/FcmService.php`):**
```php
class FcmService
{
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        if (!$user->fcm_token) return;

        $message = CloudMessage::withTarget('token', $user->fcm_token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        app('firebase.messaging')->send($message);
    }

    public function sendToRole(string $role, string $title, string $body, array $data = []): void
    {
        User::role($role)->whereNotNull('fcm_token')->each(function (User $user) use ($title, $body, $data) {
            $this->sendToUser($user, $title, $body, $data);
        });
    }

    // Notifikasi presensi masuk ke admin
    public function notifyPresensiSubmitted(TpqClass $class, string $date, int $count, User $teacher): void
    {
        $this->sendToRole('admin', 
            '✅ Presensi Masuk',
            "Ust. {$teacher->name} sudah input presensi kelas {$class->name} ({$count} santri) - {$date}",
            ['type' => 'presensi', 'class_id' => $class->id, 'date' => $date]
        );
    }

    // Notifikasi capaian/nilai diinput
    public function notifyCapaianUpdated(TpqStudent $student, User $teacher, string $subject): void
    {
        $this->sendToRole('admin',
            '📊 Nilai Diperbarui',
            "Ust. {$teacher->name} memperbarui nilai {$subject} untuk {$student->name}",
            ['type' => 'capaian', 'student_id' => $student->id]
        );
    }

    // Reminder SPP ke ustadz (untuk ustadz sampaikan ke wali saat pertemuan)
    public function remindSppToTeachers(int $month, int $year): void
    {
        $unpaidCount = TpqSppBill::unpaid($month, $year)->count();
        if ($unpaidCount === 0) return;

        $this->sendToRole('ustadz',
            '💳 Reminder SPP',
            "Ada {$unpaidCount} santri belum bayar SPP " . now()->setMonth($month)->format('F Y') . ". Mohon sampaikan ke wali murid.",
            ['type' => 'spp_reminder', 'month' => $month, 'year' => $year]
        );
    }
}
```

**Tambah kolom FCM token ke users table:**
```php
// migration: add_fcm_token_to_users_table
$table->string('fcm_token')->nullable()->after('avatar');
```

---

### 22.5 Implementasi Flutter — Screen by Screen

#### A. `main.dart`

```dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  await dotenv.load(fileName: '.env');

  // Setup local notifications
  await LocalNotificationService.init();

  // Setup FCM background handler
  FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

  runApp(ProviderScope(child: SiMasjidApp()));
}

@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  await LocalNotificationService.showFromFcm(message);
}
```

#### B. `app_theme.dart`

```dart
class AppTheme {
  // Warna brand (sync dengan Tailwind primary-600)
  static const primaryColor = Color(0xFF16A34A);
  static const primaryLight = Color(0xFF4ADE80);
  static const goldColor    = Color(0xFFF59E0B);

  static ThemeData light() => ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: primaryColor,
      brightness: Brightness.light,
    ),
    fontFamily: GoogleFonts.plusJakartaSans().fontFamily,
    scaffoldBackgroundColor: const Color(0xFFF8FAFC),
    appBarTheme: const AppBarTheme(
      backgroundColor: Colors.white,
      foregroundColor: Color(0xFF0F172A),
      elevation: 0,
      centerTitle: false,
    ),
    cardTheme: CardTheme(
      color: Colors.white,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
    ),
  );

  static ThemeData dark() => ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: primaryColor,
      brightness: Brightness.dark,
      surface: const Color(0xFF1E293B),
    ),
    fontFamily: GoogleFonts.plusJakartaSans().fontFamily,
    scaffoldBackgroundColor: const Color(0xFF0F172A),
    appBarTheme: const AppBarTheme(
      backgroundColor: Color(0xFF1E293B),
      foregroundColor: Color(0xFFF1F5F9),
      elevation: 0,
    ),
    cardTheme: CardTheme(
      color: const Color(0xFF1E293B),
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: Color(0xFF334155)),
      ),
    ),
  );
}
```

#### C. `login_screen.dart`

```dart
// Desain:
// - Background gradien hijau gelap (brand)
// - Logo SiMasjid + nama masjid di tengah atas
// - Card putih/dark di tengah berisi form
// - Field: Nomor HP + Password (obscure toggle)
// - Tombol Login (full width, hijau, loading state)
// - Versi app di bawah

class LoginScreen extends ConsumerStatefulWidget { ... }

// Logic:
// 1. Tap login → panggil AuthRepository.login(phone, password)
// 2. Sukses → simpan token di SecureStorage, simpan user info di SharedPrefs
// 3. Update FCM token ke backend
// 4. Navigate ke DashboardScreen
// 5. Gagal → tampil SnackBar error
```

#### D. `dashboard_screen.dart`

```dart
// Layout:
// AppBar: "Assalamu'alaikum, Ust. [Nama]" + avatar + notif bell (badge count)
// Body:
//   - Card info masjid + tanggal (Hijriah + Masehi)
//   - Grid 2×2 Stat: Santri Kelas Saya | Hadir Hari Ini | Belum Absen | SPP Outstanding
//   - Section "Aksi Cepat" (4 tombol besar):
//       [📋 Presensi Hari Ini] [📊 Capaian Santri] [📖 Buka Portal Web] [🔔 Notifikasi]
//   - Section "Kelas Saya" — list kelas yang diampu ustadz ini
//   - Section "Aktivitas Terbaru" — log presensi/nilai yang baru diinput

// BottomNavigationBar (3 tab):
//   🏠 Beranda  |  📋 Presensi  |  📊 Capaian
// Floating action: 🌐 Buka Web (→ WebView screen)
```

#### E. `presensi_kelas_screen.dart`

```dart
// List kelas yang diampu ustadz ini
// Setiap item: nama kelas, jumlah santri, status presensi hari ini
//   - Belum: badge merah "Belum Absen"
//   - Sudah: badge hijau "Sudah Absen (X hadir)"
// Tap kelas → PresensiInputScreen
```

#### F. `presensi_input_screen.dart`

```dart
// AppBar: "Presensi [Nama Kelas] — [Tanggal]"
// 
// STEP 1 — Verifikasi Lokasi (muncul di awal):
// Widget lokasi di atas: 
//   - Animasi radar/pulse (Lottie)
//   - Teks: "Mengambil lokasi Anda..."
//   - Setelah dapat: tampil "📍 X meter dari masjid"
//   - Dalam radius 500m: ✅ hijau — bisa lanjut
//   - Di luar radius: ❌ merah — tampil dialog peringatan
//   - Tombol "Coba Lagi" jika gagal dapat GPS
//
// STEP 2 — Input Kehadiran:
// List santri (dari API) dengan card per santri:
//   - Foto santri (cached_network_image + placeholder avatar)
//   - Nama santri + NIS
//   - 4 tombol status: [✅ Hadir] [📋 Izin] [🤒 Sakit] [❌ Alfa]
//     (toggle button, satu aktif, warna berbeda per status)
//   - Field keterangan (muncul jika status bukan Hadir)
//
// Sticky bottom bar:
//   - Progress: "X / Y santri diisi"
//   - Tombol "Simpan Presensi" (disabled jika belum semua terisi)
//   - Loading state + animasi sukses (Lottie checkmark)
//
// Setelah simpan → dialog sukses → kembali ke kelas list
```

**Contoh kode lokasi di `presensi_input_screen.dart`:**
```dart
Future<void> _validateLocation() async {
  setState(() => _locationStatus = LocationStatus.loading);

  // Cek permission
  LocationPermission permission = await Geolocator.checkPermission();
  if (permission == LocationPermission.denied) {
    permission = await Geolocator.requestPermission();
  }
  if (permission == LocationPermission.deniedForever) {
    _showPermissionDialog();
    return;
  }

  // Ambil posisi
  final position = await Geolocator.getCurrentPosition(
    desiredAccuracy: LocationAccuracy.high,
    timeLimit: const Duration(seconds: 15),
  );

  // Hitung jarak ke masjid
  final distance = Geolocator.distanceBetween(
    widget.masjidLat, widget.masjidLng,
    position.latitude, position.longitude,
  );

  setState(() {
    _currentPosition = position;
    _distanceToMasjid = distance;
    _locationStatus = distance <= 500
        ? LocationStatus.valid
        : LocationStatus.tooFar;
  });
}
```

#### G. `capaian_kelas_screen.dart`

```dart
// Pilih kelas → tampil daftar santri
// Setiap santri: foto, nama, progres hafalan (linear progress bar), nilai rata-rata
// Tap santri → CapaianDetailScreen
```

#### H. `capaian_detail_screen.dart`

```dart
// AppBar: "Capaian [Nama Santri]"
// Tab bar: [📊 Nilai] [📖 Hafalan]
//
// TAB NILAI:
// - Semester selector (dropdown)
// - List mapel dengan nilai + deskripsi
// - Nilai rata-rata di bawah
// - Tombol "Input/Update Nilai" (→ InputNilaiScreen)
//
// TAB HAFALAN:
// - Progress bar keseluruhan (X dari 114 surah)
// - Filter: [Semua] [Sudah Hafal] [Sedang] [Belum]
// - Grid surah (3 kolom):
//   Setiap item: nomor + nama surah + status (warna card)
//   Tap → modal update status + jumlah ayat
```

#### I. `webview_screen.dart`

```dart
class WebViewScreen extends ConsumerStatefulWidget {
  final String? initialPath; // misal '/admin/dashboard'
  // ...
}

class _WebViewScreenState extends ConsumerState<WebViewScreen> {
  late WebViewController _controller;

  @override
  void initState() {
    super.initState();
    _initWebView();
  }

  Future<void> _initWebView() async {
    // Ambil short-lived webview token dari API
    final result = await ref.read(authRepositoryProvider).getWebviewToken();
    final baseUrl = dotenv.env['APP_URL']!;
    final path = widget.initialPath ?? '/admin/dashboard';

    // URL dengan token query parameter
    // Backend akan auto-login dan set session
    final url = '$baseUrl/mobile-login?token=${result.token}&redirect=$path';

    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setUserAgent('SiMasjidApp/1.0 Flutter Android')
      ..setNavigationDelegate(NavigationDelegate(
        onPageStarted: (_) => setState(() => _isLoading = true),
        onPageFinished: (_) => setState(() => _isLoading = false),
        onNavigationRequest: (request) {
          // Intercept link ke halaman presensi/capaian → buka native screen
          if (request.url.contains('/tpq/absensi')) {
            Navigator.pushNamed(context, '/presensi');
            return NavigationDecision.prevent;
          }
          return NavigationDecision.navigate;
        },
      ))
      ..loadRequest(Uri.parse(url));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Portal Masjid'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => _controller.reload(),
          ),
        ],
      ),
      body: Stack(
        children: [
          WebViewWidget(controller: _controller),
          if (_isLoading) const LinearProgressIndicator(),
        ],
      ),
    );
  }
}
```

**Route mobile-login di Laravel (`web.php`):**
```php
// Auto-login via token untuk WebView
Route::get('/mobile-login', function (Request $request) {
    $token   = $request->query('token');
    $redirect = $request->query('redirect', '/admin/dashboard');

    // Verifikasi token Sanctum
    $accessToken = PersonalAccessToken::findToken($token);
    if (!$accessToken || !$accessToken->can('webview')) {
        abort(401);
    }

    // Login user ke session web
    Auth::login($accessToken->tokenable);

    // Hapus token webview (single use)
    $accessToken->delete();

    return redirect($redirect);
})->name('mobile.login');
```

---

### 22.6 Push Notification — Semua Skenario

| Trigger | Penerima | Isi Notifikasi | Tap Action |
|---|---|---|---|
| Ustadz submit presensi | Admin/Kepala TPQ | "✅ Ust. X input presensi Kelas Y (Z santri)" | Buka rekap presensi (WebView) |
| Santri alfa 3x berturut-turut | Admin + Wali Murid (WA) | "⚠️ [Nama] tidak hadir 3 hari berturut-turut" | Buka detail santri (WebView) |
| Nilai diinput ustadz | Admin | "📊 Nilai [Mapel] untuk [Santri] telah diperbarui" | Buka halaman nilai (WebView) |
| SPP belum bayar (H+7) | Ustadz pengampu | "💳 X santri belum bayar SPP [Bulan]. Sampaikan ke wali." | Buka list SPP kelas (native) |
| SPP belum bayar (H+7) | Wali Murid (WA) | "SPP [Nama] bulan [X] belum terbayar" | — (via WA) |
| Raport siap diambil | Ustadz | "📋 Raport Semester X sudah bisa didownload/dibagikan" | Buka halaman raport (WebView) |
| Pengumuman baru dari admin | Semua ustadz | "[Judul Pengumuman]" | Buka pengumuman (WebView) |
| Jadwal imam H-1 | Imam terjadwal | "Mengingatkan: Anda imam [Shalat] besok pukul [Jam]" | Buka jadwal imam (WebView) |

**`fcm_notifications` table (untuk riwayat notifikasi in-app):**
```php
Schema::create('fcm_notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->string('title');
    $table->text('body');
    $table->string('type'); // presensi|capaian|spp|raport|pengumuman|imam
    $table->json('data')->nullable();
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
});
```

---

### 22.7 Setup Firebase

```bash
# 1. Install FlutterFire CLI
dart pub global activate flutterfire_cli

# 2. Login ke Firebase
firebase login

# 3. Init Firebase ke project Flutter
flutterfire configure --project=simasjid-app

# Ini akan generate: lib/firebase_options.dart
# Dan menambahkan google-services.json ke android/app/
```

**`android/app/build.gradle`:**
```gradle
android {
    defaultConfig {
        applicationId "com.simasjid.app"
        minSdkVersion 21
        targetSdkVersion 34
        versionCode 1
        versionName "1.0.0"
    }
    signingConfigs {
        release {
            keyAlias keystoreProperties['keyAlias']
            keyPassword keystoreProperties['keyPassword']
            storeFile file(keystoreProperties['storeFile'])
            storePassword keystoreProperties['storePassword']
        }
    }
    buildTypes {
        release {
            signingConfig signingConfigs.release
            minifyEnabled true
            shrinkResources true
        }
    }
}

dependencies {
    implementation 'com.google.firebase:firebase-messaging-ktx'
}
```

**`AndroidManifest.xml` — permissions:**
```xml
<uses-permission android:name="android.permission.INTERNET"/>
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION"/>
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION"/>
<uses-permission android:name="android.permission.RECEIVE_BOOT_COMPLETED"/>
<uses-permission android:name="android.permission.VIBRATE"/>
<uses-permission android:name="android.permission.POST_NOTIFICATIONS"/>

<!-- FCM service -->
<service
    android:name="com.google.firebase.messaging.FirebaseMessagingService"
    android:exported="false">
    <intent-filter>
        <action android:name="com.google.firebase.MESSAGING_EVENT"/>
    </intent-filter>
</service>
```

---

### 22.8 Build & Distribusi APK

```bash
# Debug APK (testing internal)
flutter build apk --debug

# Release APK (distribusi langsung / Play Store)
flutter build apk --release --obfuscate --split-debug-info=build/debug-info

# AAB untuk Google Play Store
flutter build appbundle --release

# APK tersimpan di:
# build/app/outputs/flutter-apk/app-release.apk

# Install langsung ke HP via USB:
flutter install --release
```

**Distribusi langsung (tanpa Play Store):**
- Upload APK ke Google Drive / Telegram grup pengurus
- Ustadz install via "Install dari sumber tidak dikenal"
- Update manual: bagikan APK versi baru + panduan update

---

### 22.9 Struktur File Env Flutter

```
# .env (di root project Flutter)
APP_URL=https://simasjid.yourdomain.com
API_BASE_URL=https://simasjid.yourdomain.com/api/mobile/v1
MASJID_RADIUS_METERS=500
APP_VERSION=1.0.0
```

---

### 22.10 Checklist Flutter Khusus

- [ ] `pubspec.yaml` lengkap + `flutter pub get`
- [ ] Firebase project dibuat + `flutterfire configure` dijalankan
- [ ] `google-services.json` masuk ke `android/app/`
- [ ] `.env` Flutter dikonfigurasi dengan URL backend
- [ ] Signing keystore dibuat untuk release build
- [ ] Backend: migration tambah `fcm_token` ke `users`
- [ ] Backend: migration tambah `latitude/longitude` ke `tpq_attendances`
- [ ] Backend: `routes/api.php` mobile routes ditambahkan
- [ ] Backend: `MobileAuthController` + webview token endpoint
- [ ] Backend: `MobilePresensiController` dengan validasi Haversine
- [ ] Backend: `FcmService` + install `kreait/firebase-php`
- [ ] Backend: `fcm_notifications` table + model
- [ ] Backend: `route mobile-login` untuk WebView auto-auth
- [ ] Flutter: `AppTheme` light + dark sync dengan brand web
- [ ] Flutter: `LoginScreen` native
- [ ] Flutter: `DashboardScreen` dengan stat + quick actions
- [ ] Flutter: `PresensiKelasScreen` + `PresensiInputScreen` + GPS validation
- [ ] Flutter: `CapaianKelasScreen` + `CapaianDetailScreen` (nilai + hafalan)
- [ ] Flutter: `WebViewScreen` dengan token inject
- [ ] Flutter: FCM foreground + background handler
- [ ] Flutter: Local notifications untuk FCM background
- [ ] Flutter: Push notif tap → routing ke screen yang tepat
- [ ] Build APK release + test di HP fisik
- [ ] Panduan install APK untuk ustadz (PDF 1 halaman)

---

> **SiMasjid** — *Masjid Modern, Jamaah Sejahtera*  
> Dibuat dengan ❤️ untuk kemajuan masjid-masjid Indonesia
