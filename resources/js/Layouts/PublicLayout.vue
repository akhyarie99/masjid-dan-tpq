<template>
  <div class="min-h-screen flex flex-col">
    <header class="sticky top-0 z-30 border-b border-[var(--border)] bg-[var(--bg-surface)]/90 backdrop-blur">
      <div class="max-w-6xl mx-auto px-4 md:px-6 h-16 flex items-center gap-4">
        <Link :href="route('home')" class="flex items-center gap-2 shrink-0">
          <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold text-sm">
            {{ masjidInitial }}
          </div>
          <span class="font-semibold text-[var(--text-primary)] truncate">{{ masjidName }}</span>
        </Link>

        <nav class="hidden md:flex items-center gap-1 ml-4">
          <Link
            v-for="item in navItems"
            :key="item.label"
            :href="route(item.route)"
            class="px-3 py-2 rounded-lg text-sm font-medium text-[var(--text-primary)] hover:bg-[var(--bg-muted)] transition-colors"
          >
            {{ item.label }}
          </Link>
        </nav>

        <div class="flex-1" />

        <ThemeToggle />

        <Link
          v-if="route().has('wali.login')"
          :href="route('wali.login')"
          class="btn-secondary !text-xs md:!text-sm"
        >
          Portal Wali
        </Link>

        <Link
          v-if="route().has('login')"
          :href="route('login')"
          class="btn-primary !text-xs md:!text-sm"
        >
          Masuk
        </Link>
      </div>
    </header>

    <main class="flex-1">
      <slot />
    </main>

    <footer class="border-t border-[var(--border)] bg-[var(--bg-surface)]">
      <div class="max-w-6xl mx-auto px-4 md:px-6 py-8 text-sm text-[var(--text-muted)] flex flex-col md:flex-row items-center justify-between gap-2">
        <p>&copy; {{ year }} {{ masjidName }}. Dikelola dengan SiMasjid.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import ThemeToggle from '@/Components/Shared/ThemeToggle.vue'

const page = usePage()
const masjidName = computed(() => page.props.masjid?.name ?? 'SiMasjid')
const masjidInitial = computed(() => masjidName.value.charAt(0).toUpperCase())
const year = new Date().getFullYear()

const navItems = [
  { label: 'Beranda', route: 'home' },
  { label: 'Donasi', route: 'public.donation' },
  { label: 'Laporan Keuangan', route: 'public.finance' },
  { label: 'Jadwal Imam', route: 'public.imam' },
  { label: 'Kegiatan', route: 'public.activities' },
].filter((item) => route().has(item.route))
</script>
