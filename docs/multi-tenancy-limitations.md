# Limitasi Multi-Tenancy (SaaS White-Label)

Dokumen ini mencatat batasan yang diketahui dan sengaja belum diperbaiki saat
sistem ini diubah dari single-tenant jadi multi-tenant SaaS. Bukan bug yang
terlewat — ini keputusan sadar untuk membatasi scope pekerjaan awal.

## 1. Role Spatie masih global, bukan per-tenant

Role (`admin`, `ustadz`, `bendahara`, dst — lihat `database/seeders/RolePermissionSeeder.php`)
adalah baris database yang dipakai bersama SEMUA tenant, bukan didefinisikan
ulang per lembaga. Ini aman selama tidak ada fitur di UI yang membiarkan
tenant mengedit *permission* suatu role (sekarang memang tidak ada — tenant
cuma bisa *assign* role yang sudah ada ke user mereka sendiri, dan itu sudah
di-scope benar lewat `masjid_id` di tabel `users`).

**Kapan ini jadi masalah**: begitu ada fitur "custom permission per lembaga",
ini harus dibereskan dulu — kemungkinan lewat fitur *teams* dari
`spatie/laravel-permission` (belum diaktifkan sama sekali di app ini).

## 2. `WaliAccount.phone` adalah identifier global lintas tenant

Akun wali (orang tua/wali santri) tidak punya kolom `masjid_id` sendiri —
terhubung ke tenant hanya secara tidak langsung lewat relasi
`students() -> TpqStudent.masjid_id`. `WaliAccount::syncForStudent()` mencari
akun berdasarkan `firstOrCreate(['phone' => ...])` tanpa filter tenant sama
sekali.

**Konsekuensi**: dua lembaga berbeda yang kebetulan punya wali dengan nomor
HP yang sama akan numpuk di satu baris `WaliAccount` yang sama — wali itu
berpotensi melihat data anak dari kedua lembaga sekaligus.

**Kenapa belum diperbaiki**: perlu perubahan skema `WaliAccount` yang cukup
besar (nomor HP jadi unique per-tenant, bukan global) dan menyentuh alur
login/lupa-password wali yang sudah ada. Login staf/admin sudah diperbaiki
(lihat `AuthController`/`MobileAuthController`), login wali sengaja dibiarkan
seperti semula untuk saat ini.

## 3. Reverb (live donation ticker) satu proses untuk semua tenant

Nama channel broadcast sudah per-masjid (`masjid.{id}.donations`, lihat
`app/Events/DonationReceived.php`), jadi secara penamaan sudah benar. Yang
belum: hanya ada SATU proses Reverb (`deploy/supervisor.conf`) yang melayani
semua tenant sekaligus, tanpa isolasi kredensial per tenant di level
transport — dan channel-nya publik (`Channel`, bukan `PrivateChannel` dengan
otorisasi server), yang memang disengaja untuk ticker donasi yang publik,
tapi perlu dipastikan tetap sesuai keinginan di dunia multi-tenant (siapa pun
yang tahu `masjid_id` tenant lain bisa subscribe ke channel donasi mereka).

## 4. Aplikasi mobile (Flutter, staf/ustadz) belum multi-tenant

Endpoint API (`MobileAuthController`) sudah di-scope tenant lewat
`ResolveTenant` middleware + host request, tapi app Flutter yang sudah ada
sekarang masih hardcode base URL API ke domain lama. Rencana ke depan:
lembaga lain dapat *flavor* build Android sendiri (logo/splash sendiri, base
URL API tenant masing-masing di-hardcode saat build) — bukan satu app
generik dengan UI pilih-lembaga. Sebelum tenant lama pindah ke subdomain
barunya, app yang sudah live HARUS di-update base URL-nya juga, kalau tidak
staf yang pakai app itu akan gagal login begitu domain lama jadi hub SaaS.
