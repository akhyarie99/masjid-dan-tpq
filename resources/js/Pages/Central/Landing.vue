<template>
  <Head title="SiMasjid — Sistem Informasi Manajemen Masjid & TPQ" />

  <div class="min-h-screen bg-[var(--bg-base)]">
    <header class="flex items-center justify-between p-4 md:p-6 max-w-5xl mx-auto">
      <div class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold">🕌</div>
        <span class="font-semibold text-[var(--text-primary)]">SiMasjid</span>
      </div>
      <div class="flex items-center gap-3">
        <ThemeToggle />
        <Link :href="route('central.register')" class="btn-primary">Daftarkan Lembaga</Link>
      </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 md:px-6 py-12 md:py-20 text-center">
      <h1 class="text-3xl md:text-5xl font-bold text-[var(--text-primary)] mb-4">
        Sistem Informasi Masjid &amp; TPQ untuk Lembaga Anda
      </h1>
      <p class="text-lg text-[var(--text-muted)] max-w-2xl mx-auto mb-8">
        Kelola keuangan, aset, kegiatan, dan TPQ dalam satu platform — dengan portal publik,
        portal wali santri, dan aplikasi presensi staf. Setiap lembaga dapat alamat portalnya sendiri.
      </p>
      <Link :href="route('central.register')" class="btn-primary text-base px-6 py-3 inline-block">
        Daftarkan Lembaga Anda — Gratis Dicoba
      </Link>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-16 text-left">
        <div class="card p-6">
          <div class="text-2xl mb-2">🏛️</div>
          <h3 class="font-semibold text-[var(--text-primary)] mb-1">Portal Sendiri</h3>
          <p class="text-sm text-[var(--text-muted)]">Setiap lembaga otomatis dapat alamat portal sendiri, bisa upgrade ke domain milik sendiri kapan saja.</p>
        </div>
        <div class="card p-6">
          <div class="text-2xl mb-2">📚</div>
          <h3 class="font-semibold text-[var(--text-primary)] mb-1">Manajemen TPQ Lengkap</h3>
          <p class="text-sm text-[var(--text-muted)]">Nilai, hafalan, progres harian, Infaq, raport, hingga portal khusus wali santri.</p>
        </div>
        <div class="card p-6">
          <div class="text-2xl mb-2">💰</div>
          <h3 class="font-semibold text-[var(--text-primary)] mb-1">Keuangan &amp; Aset</h3>
          <p class="text-sm text-[var(--text-muted)]">Pencatatan kas, donasi, aset, hingga laporan pertanggungjawaban.</p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ThemeToggle from '@/Components/Shared/ThemeToggle.vue'
</script>
