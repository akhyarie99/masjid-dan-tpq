<template>
  <Head title="Dashboard" />

  <AdminLayout title="Dashboard">
    <PageHeader title="Dashboard" description="Ringkasan aktivitas masjid Anda hari ini.">
      <template #actions>
        <Link v-if="route().has('admin.finance.transaksi.create')" :href="route('admin.finance.transaksi.create')" class="btn-secondary">
          <PlusIcon class="w-4 h-4" /> Transaksi
        </Link>
        <Link v-if="route().has('admin.activity.create')" :href="route('admin.activity.create')" class="btn-secondary">
          <PlusIcon class="w-4 h-4" /> Kegiatan
        </Link>
        <Link v-if="route().has('pengumuman.create')" :href="route('pengumuman.create')" class="btn-secondary">
          <MegaphoneIcon class="w-4 h-4" /> Pengumuman
        </Link>
        <Link v-if="route().has('admin.finance.laporan')" :href="route('admin.finance.laporan')" class="btn-primary">
          <PrinterIcon class="w-4 h-4" /> Laporan
        </Link>
      </template>
    </PageHeader>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard label="Saldo Kas Total" :value="formatCurrency(stats.totalKas)" :icon="Wallet" />
      <StatCard label="Pemasukan Bulan Ini" :value="formatCurrency(stats.incomeThisMonth)" :icon="TrendingUp" icon-bg="bg-green-50 dark:bg-green-900/20" icon-color="text-green-600 dark:text-green-400" />
      <StatCard label="Pengeluaran Bulan Ini" :value="formatCurrency(stats.expenseThisMonth)" :icon="TrendingDown" icon-bg="bg-red-50 dark:bg-red-900/20" icon-color="text-red-600 dark:text-red-400" />
      <StatCard label="Santri TPQ Aktif" :value="stats.activeStudents" :icon="Users" icon-bg="bg-blue-50 dark:bg-blue-900/20" icon-color="text-blue-600 dark:text-blue-400" />
    </div>

    <!-- Chart -->
    <AppCard title="Pemasukan vs Pengeluaran" subtitle="12 bulan terakhir" class="mb-6">
      <div class="h-72">
        <Line :data="chartData" :options="chartOptions" />
      </div>
    </AppCard>

    <!-- Widgets -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <AppCard title="Kegiatan Mendatang">
        <div v-if="upcomingActivities.length === 0" class="text-center text-sm text-[var(--text-muted)] py-6">
          Belum ada kegiatan mendatang.
        </div>
        <ul v-else class="space-y-3">
          <li v-for="activity in upcomingActivities" :key="activity.id" class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-sm font-medium text-[var(--text-primary)] truncate">{{ activity.name }}</p>
              <p class="text-xs text-[var(--text-muted)]">{{ formatDate(activity.start_at) }}</p>
            </div>
            <AppBadge variant="green">{{ daysUntil(activity.start_at) }}</AppBadge>
          </li>
        </ul>
      </AppCard>

      <AppCard title="Aset Perlu Perhatian">
        <div v-if="assetsNeedingAttention.length === 0" class="text-center text-sm text-[var(--text-muted)] py-6">
          Semua aset dalam kondisi baik.
        </div>
        <ul v-else class="space-y-3">
          <li v-for="asset in assetsNeedingAttention" :key="asset.id" class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-sm font-medium text-[var(--text-primary)] truncate">{{ asset.name }}</p>
              <p class="text-xs text-[var(--text-muted)] truncate">{{ asset.location }}</p>
            </div>
            <AppBadge :variant="asset.condition.startsWith('rusak') ? 'red' : 'yellow'">
              {{ conditionLabel(asset.condition) }}
            </AppBadge>
          </li>
        </ul>
      </AppCard>

      <AppCard title="Donasi Terbaru">
        <div v-if="latestDonations.length === 0" class="text-center text-sm text-[var(--text-muted)] py-6">
          Belum ada donasi masuk.
        </div>
        <ul v-else class="space-y-3">
          <li v-for="donation in latestDonations" :key="donation.id" class="flex items-center justify-between gap-3">
            <p class="text-sm text-[var(--text-primary)] truncate">{{ donation.donor_name ?? 'Hamba Allah' }}</p>
            <p class="text-sm font-semibold text-primary-600 shrink-0">{{ formatCurrency(donation.amount) }}</p>
          </li>
        </ul>
      </AppCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend, Filler,
} from 'chart.js'
import dayjs from 'dayjs'
import {
  Wallet, TrendingUp, TrendingDown, Users, Plus as PlusIcon, Megaphone as MegaphoneIcon, Printer as PrinterIcon,
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import StatCard from '@/Components/Shared/StatCard.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend, Filler)

const props = defineProps({
  stats: { type: Object, required: true },
  chart: { type: Object, required: true },
  upcomingActivities: { type: Array, default: () => [] },
  assetsNeedingAttention: { type: Array, default: () => [] },
  latestDonations: { type: Array, default: () => [] },
})

const chartData = computed(() => ({
  labels: props.chart.labels,
  datasets: [
    {
      label: 'Pemasukan',
      data: props.chart.income,
      borderColor: '#16a34a',
      backgroundColor: 'rgba(22, 163, 74, 0.1)',
      tension: 0.3,
      fill: true,
    },
    {
      label: 'Pengeluaran',
      data: props.chart.expense,
      borderColor: '#ef4444',
      backgroundColor: 'rgba(239, 68, 68, 0.1)',
      tension: 0.3,
      fill: true,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY, HH:mm')
}

function daysUntil(value) {
  const diff = dayjs(value).startOf('day').diff(dayjs().startOf('day'), 'day')
  if (diff === 0) return 'Hari ini'
  if (diff === 1) return 'Besok'
  return `${diff} hari lagi`
}

function conditionLabel(condition) {
  return { baik: 'Baik', cukup: 'Cukup', rusak_ringan: 'Rusak Ringan', rusak_berat: 'Rusak Berat' }[condition] ?? condition
}
</script>
