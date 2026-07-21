<template>
  <Head :title="masjid.name" />

  <PublicLayout>
    <!-- SECTION 1 — HERO -->
    <section class="relative bg-gradient-to-b from-emerald-950 to-emerald-900 text-white py-14 md:py-20 px-4">
      <div class="max-w-4xl mx-auto flex flex-col items-center gap-8">
        <DigitalClock />
        <PrayerCountdown :prayer-times="todaySchedule" />
      </div>
    </section>

    <!-- SECTION 2 — JADWAL SHALAT HARI INI -->
    <section class="max-w-6xl mx-auto px-4 md:px-6 -mt-8 md:-mt-10 relative z-10">
      <div class="grid grid-cols-3 md:grid-cols-6 gap-2 md:gap-3">
        <div
          v-for="prayer in prayers"
          :key="prayer.key"
          class="card p-3 md:p-4 text-center"
          :class="activePrayer === prayer.key ? 'ring-2 ring-primary-500 animate-pulse' : ''"
        >
          <p class="text-xs text-[var(--text-muted)]">{{ prayer.label }}</p>
          <p class="text-base md:text-lg font-bold text-[var(--text-primary)] tabular-nums mt-1">{{ prayer.time ?? '--:--' }}</p>
        </div>
      </div>
    </section>

    <div class="max-w-6xl mx-auto px-4 md:px-6 py-10 md:py-14 space-y-10">
      <!-- SECTION 3 — PENGUMUMAN TERBARU -->
      <section>
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg md:text-xl font-bold text-[var(--text-primary)]">Pengumuman Terbaru</h2>
        </div>
        <EmptyState v-if="announcements.length === 0" title="Belum ada pengumuman" :icon="MegaphoneIcon" />
        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="item in announcements" :key="item.id" class="card p-4">
            <AppBadge :variant="typeVariant(item.type)">{{ typeLabel(item.type) }}</AppBadge>
            <p class="font-semibold text-[var(--text-primary)] mt-2">{{ item.title }}</p>
            <p class="text-sm text-[var(--text-muted)] mt-1 line-clamp-3">{{ item.content }}</p>
          </div>
        </div>
      </section>

      <!-- SECTION 4 — KEGIATAN MENDATANG -->
      <section>
        <h2 class="text-lg md:text-xl font-bold text-[var(--text-primary)] mb-4">Kegiatan Mendatang</h2>
        <EmptyState v-if="activities.length === 0" title="Belum ada kegiatan mendatang" :icon="CalendarIcon" />
        <ul v-else class="space-y-3">
          <li v-for="activity in activities" :key="activity.id" class="card p-4 flex items-center justify-between gap-4">
            <div class="min-w-0">
              <p class="font-medium text-[var(--text-primary)] truncate">{{ activity.name }}</p>
              <p class="text-sm text-[var(--text-muted)]">{{ formatDate(activity.start_at) }} · {{ activity.location }}</p>
            </div>
            <a
              v-if="activity.registration_link"
              :href="activity.registration_link"
              target="_blank"
              rel="noopener"
              class="btn-secondary shrink-0"
            >
              Daftar
            </a>
          </li>
        </ul>
      </section>

      <!-- SECTION 5 — JADWAL IMAM PEKAN INI -->
      <section>
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg md:text-xl font-bold text-[var(--text-primary)]">Jadwal Imam Pekan Ini</h2>
          <Link v-if="route().has('public.imam')" :href="route('public.imam')" class="text-sm text-primary-600 hover:underline">
            Lihat Jadwal Lengkap
          </Link>
        </div>
        <EmptyState v-if="imamSchedules.length === 0" title="Jadwal imam belum tersedia" :icon="UsersIcon" />
        <div v-else class="table-responsive card !p-0">
          <table class="table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Shalat</th>
                <th>Imam</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="schedule in imamSchedules" :key="schedule.id">
                <td>{{ formatDate(schedule.date) }}</td>
                <td class="capitalize">{{ schedule.prayer }}</td>
                <td>{{ schedule.imam?.name ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- SECTION 6 — DONASI DIGITAL -->
      <section>
        <h2 class="text-lg md:text-xl font-bold text-[var(--text-primary)] mb-4">Donasi Digital</h2>
        <DonationWidget
          :this-month="donation.thisMonth"
          :latest-donors="donation.latestDonors"
          :bank-accounts="masjid.bank_accounts ?? []"
        />
      </section>

      <!-- SECTION 7 — TRANSPARANSI KEUANGAN -->
      <section>
        <h2 class="text-lg md:text-xl font-bold text-[var(--text-primary)] mb-4">Transparansi Keuangan</h2>
        <div class="card p-6">
          <div class="grid grid-cols-3 gap-4 text-center">
            <div>
              <p class="text-xs md:text-sm text-[var(--text-muted)]">Pemasukan</p>
              <p class="text-lg md:text-xl font-bold text-green-600 mt-1">{{ formatCurrency(finance.income) }}</p>
            </div>
            <div>
              <p class="text-xs md:text-sm text-[var(--text-muted)]">Pengeluaran</p>
              <p class="text-lg md:text-xl font-bold text-red-500 mt-1">{{ formatCurrency(finance.expense) }}</p>
            </div>
            <div>
              <p class="text-xs md:text-sm text-[var(--text-muted)]">Saldo</p>
              <p class="text-lg md:text-xl font-bold text-primary-600 mt-1">{{ formatCurrency(finance.balance) }}</p>
            </div>
          </div>
          <Link
            v-if="route().has('public.finance')"
            :href="route('public.finance')"
            class="btn-secondary w-full justify-center mt-6"
          >
            Lihat Laporan Lengkap
          </Link>
        </div>
      </section>

      <!-- SECTION 8 — INFO MASJID -->
      <section>
        <h2 class="text-lg md:text-xl font-bold text-[var(--text-primary)] mb-4">Info Masjid</h2>
        <div class="card p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2 text-sm">
            <p class="flex items-start gap-2 text-[var(--text-primary)]">
              <MapPinIcon class="w-4 h-4 mt-0.5 shrink-0 text-primary-600" /> {{ masjid.address }}
            </p>
            <p v-if="masjid.phone" class="flex items-center gap-2 text-[var(--text-primary)]">
              <PhoneIcon class="w-4 h-4 shrink-0 text-primary-600" /> {{ masjid.phone }}
            </p>
            <div class="flex items-center gap-3 pt-2">
              <a v-if="masjid.instagram" :href="masjid.instagram" target="_blank" rel="noopener" class="text-primary-600 hover:underline text-sm">Instagram</a>
              <a v-if="masjid.youtube" :href="masjid.youtube" target="_blank" rel="noopener" class="text-primary-600 hover:underline text-sm">YouTube</a>
            </div>
          </div>
          <div v-if="masjid.latitude && masjid.longitude" class="rounded-xl overflow-hidden aspect-video">
            <iframe
              class="w-full h-full"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              :src="`https://www.google.com/maps?q=${masjid.latitude},${masjid.longitude}&output=embed`"
            />
          </div>
        </div>
      </section>
    </div>
  </PublicLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Megaphone as MegaphoneIcon, Calendar as CalendarIcon, Users as UsersIcon, MapPin as MapPinIcon, Phone as PhoneIcon } from 'lucide-vue-next'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import DigitalClock from '@/Components/Public/DigitalClock.vue'
import PrayerCountdown from '@/Components/Public/PrayerCountdown.vue'
import DonationWidget from '@/Components/Public/DonationWidget.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import { usePrayerTime } from '@/composables/usePrayerTime'

const props = defineProps({
  masjid: { type: Object, required: true },
  todaySchedule: { type: Object, default: null },
  announcements: { type: Array, default: () => [] },
  activities: { type: Array, default: () => [] },
  imamSchedules: { type: Array, default: () => [] },
  donation: { type: Object, required: true },
  finance: { type: Object, required: true },
})

const scheduleRef = computed(() => props.todaySchedule)
const { prayers, activePrayer } = usePrayerTime(scheduleRef)

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY, HH:mm')
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

function typeLabel(type) {
  return { umum: 'Umum', kegiatan: 'Kegiatan', duka: 'Duka Cita', urgent: 'Penting' }[type] ?? type
}

function typeVariant(type) {
  return { umum: 'gray', kegiatan: 'blue', duka: 'gray', urgent: 'red' }[type] ?? 'gray'
}
</script>
