<template>
  <Head title="Ramadhan" />

  <AdminLayout title="Ramadhan">
    <PageHeader title="Mode Ramadhan & Imsakiyah" :description="`Perkiraan Ramadhan ${ramadhanYear} H: ${formatDate(period.start)} — ${formatDate(period.end)}`">
      <template #actions>
        <AppButton :loading="generating" @click="generate"><RefreshCwIcon class="w-4 h-4" /> Generate Imsakiyah</AppButton>
      </template>
    </PageHeader>

    <AppCard v-if="isRamadhanNow" class="mb-6 !bg-gradient-to-r from-emerald-700 to-emerald-900 text-white">
      <div class="flex items-center gap-3">
        <span class="text-3xl">🌙</span>
        <div>
          <p class="font-semibold">Alhamdulillah, saat ini sedang bulan Ramadhan!</p>
          <p class="text-sm text-emerald-100">{{ hijriToday.day }} Ramadhan {{ hijriToday.year }} H — Selamat Menunaikan Ibadah Puasa.</p>
        </div>
      </div>
    </AppCard>
    <AppCard v-else class="mb-6">
      <p class="text-sm text-[var(--text-muted)]">
        Saat ini bukan bulan Ramadhan (bulan Hijriah ke-{{ hijriToday.month }}, tahun {{ hijriToday.year }} H).
        Tabel imsakiyah di bawah adalah perkiraan untuk Ramadhan {{ ramadhanYear }} H mendatang.
      </p>
    </AppCard>

    <AppCard title="Jadwal Imsakiyah" :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Tanggal</th><th>Imsak</th><th>Subuh</th><th>Dzuhur</th><th>Ashar</th><th>Maghrib</th><th>Isya</th></tr></thead>
          <tbody>
            <tr v-if="imsakiyah.length === 0">
              <td colspan="7" class="text-center text-[var(--text-muted)] py-8">
                Belum ada jadwal imsakiyah. Klik "Generate Imsakiyah" untuk membuatnya.
              </td>
            </tr>
            <tr v-for="row in imsakiyah" :key="row.date">
              <td>{{ formatDate(row.date) }}</td>
              <td class="font-medium text-primary-600">{{ row.imsak }}</td>
              <td>{{ row.fajr }}</td>
              <td>{{ row.dhuhr }}</td>
              <td>{{ row.asr }}</td>
              <td>{{ row.maghrib }}</td>
              <td>{{ row.isha }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
      <Link v-if="route().has('admin.ramadhan.khatam.index')" :href="route('admin.ramadhan.khatam.index')" class="card p-4 text-center hover:bg-[var(--bg-muted)] transition-colors">
        <BookOpenIcon class="w-6 h-6 mx-auto text-primary-600 mb-2" />
        <p class="text-sm font-medium text-[var(--text-primary)]">Tracker Khatam Quran</p>
      </Link>
      <Link v-if="route().has('admin.ramadhan.itikaf.index')" :href="route('admin.ramadhan.itikaf.index')" class="card p-4 text-center hover:bg-[var(--bg-muted)] transition-colors">
        <MoonIcon class="w-6 h-6 mx-auto text-primary-600 mb-2" />
        <p class="text-sm font-medium text-[var(--text-primary)]">I'tikaf</p>
      </Link>
      <Link v-if="route().has('admin.ramadhan.qurban.index')" :href="route('admin.ramadhan.qurban.index')" class="card p-4 text-center hover:bg-[var(--bg-muted)] transition-colors">
        <BeefIcon class="w-6 h-6 mx-auto text-primary-600 mb-2" />
        <p class="text-sm font-medium text-[var(--text-primary)]">Qurban</p>
      </Link>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { RefreshCw as RefreshCwIcon, BookOpen as BookOpenIcon, Moon as MoonIcon, Beef as BeefIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppButton from '@/Components/UI/AppButton.vue'

defineProps({
  isRamadhanNow: { type: Boolean, required: true },
  hijriToday: { type: Object, required: true },
  ramadhanYear: { type: Number, required: true },
  period: { type: Object, required: true },
  imsakiyah: { type: Array, default: () => [] },
})

const generating = ref(false)

function generate() {
  generating.value = true
  router.post(route('admin.ramadhan.imsakiyah.generate'), {}, { preserveScroll: true, onFinish: () => { generating.value = false } })
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
