<template>
  <Head title="Laporan Keuangan" />

  <AdminLayout title="Laporan Keuangan">
    <PageHeader title="Laporan Keuangan" description="Ringkasan dan rincian keuangan masjid per periode.">
      <template #actions>
        <a :href="exportUrl('excel')" class="btn-secondary"><FileSpreadsheetIcon class="w-4 h-4" /> Excel</a>
        <a :href="exportUrl('pdf')" class="btn-primary"><FileTextIcon class="w-4 h-4" /> Cetak PDF</a>
      </template>
    </PageHeader>

    <AppCard class="mb-6">
      <div class="flex flex-wrap items-end gap-3">
        <div v-for="preset in presets" :key="preset.value">
          <button
            type="button"
            class="px-3 py-2 rounded-lg text-sm font-medium"
            :class="period.preset === preset.value ? 'bg-primary-600 text-white' : 'bg-[var(--bg-muted)] text-[var(--text-primary)]'"
            @click="applyPreset(preset.value)"
          >
            {{ preset.label }}
          </button>
        </div>
        <div class="flex items-end gap-2 ml-auto">
          <AppInput v-model="customFrom" type="date" label="Dari" />
          <AppInput v-model="customTo" type="date" label="Sampai" />
          <AppButton size="md" @click="applyCustom">Terapkan</AppButton>
        </div>
      </div>
    </AppCard>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="card p-5">
        <p class="text-sm text-[var(--text-muted)]">Total Pemasukan</p>
        <p class="text-xl font-bold text-green-600 mt-1">{{ formatCurrency(summary.income) }}</p>
        <p v-if="summary.incomeChangePercent !== null" class="text-xs mt-1" :class="summary.incomeChangePercent >= 0 ? 'text-green-600' : 'text-red-500'">
          {{ summary.incomeChangePercent >= 0 ? '▲' : '▼' }} {{ Math.abs(summary.incomeChangePercent) }}% vs periode lalu
        </p>
      </div>
      <div class="card p-5">
        <p class="text-sm text-[var(--text-muted)]">Total Pengeluaran</p>
        <p class="text-xl font-bold text-red-500 mt-1">{{ formatCurrency(summary.expense) }}</p>
        <p v-if="summary.expenseChangePercent !== null" class="text-xs mt-1" :class="summary.expenseChangePercent <= 0 ? 'text-green-600' : 'text-red-500'">
          {{ summary.expenseChangePercent >= 0 ? '▲' : '▼' }} {{ Math.abs(summary.expenseChangePercent) }}% vs periode lalu
        </p>
      </div>
      <div class="card p-5">
        <p class="text-sm text-[var(--text-muted)]">Saldo</p>
        <p class="text-xl font-bold text-primary-600 mt-1">{{ formatCurrency(summary.balance) }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <!-- Breakdown -->
      <AppCard title="Breakdown per Kategori" class="lg:col-span-2">
        <div v-if="breakdown.length === 0" class="text-center text-sm text-[var(--text-muted)] py-8">Tidak ada data pada periode ini.</div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
          <div class="h-64"><Doughnut :data="chartData" :options="{ maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }" /></div>
          <table class="table">
            <thead><tr><th>Kategori</th><th>Tipe</th><th>Total</th></tr></thead>
            <tbody>
              <tr v-for="(row, index) in breakdown" :key="index">
                <td>{{ row.category }}</td>
                <td>{{ row.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</td>
                <td>{{ formatCurrency(row.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </AppCard>

      <!-- QR -->
      <AppCard title="Transparansi Publik">
        <div class="flex flex-col items-center text-center gap-3">
          <img :src="qrCode" alt="QR Laporan Publik" class="w-40 h-40" />
          <p class="text-sm text-[var(--text-muted)]">Scan untuk melihat laporan keuangan di portal publik jamaah.</p>
        </div>
      </AppCard>
    </div>

    <!-- Tabel transaksi -->
    <AppCard title="Rincian Transaksi" :padded="false">
      <AppTable :columns="columns" :rows="transactions" empty-text="Tidak ada transaksi pada periode ini.">
        <template #cell-transaction_date="{ value }">{{ formatDate(value) }}</template>
        <template #cell-type="{ value }">{{ value === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</template>
        <template #cell-amount="{ row, value }">
          <span :class="row.type === 'income' ? 'text-green-600' : 'text-red-500'">{{ formatCurrency(value) }}</span>
        </template>
      </AppTable>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import { FileText as FileTextIcon, FileSpreadsheet as FileSpreadsheetIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import AppTable from '@/Components/UI/AppTable.vue'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
  period: { type: Object, required: true },
  summary: { type: Object, required: true },
  breakdown: { type: Array, default: () => [] },
  transactions: { type: Array, default: () => [] },
  qrCode: { type: String, required: true },
})

const presets = [
  { label: 'Minggu Ini', value: 'week' },
  { label: 'Bulan Ini', value: 'month' },
  { label: 'Tahun Ini', value: 'year' },
]

const customFrom = ref(props.period.from)
const customTo = ref(props.period.to)

const columns = [
  { key: 'reference_number', label: 'No. Ref' },
  { key: 'transaction_date', label: 'Tanggal' },
  { key: 'type', label: 'Tipe' },
  { key: 'description', label: 'Keterangan' },
  { key: 'amount', label: 'Nominal' },
]

const chartData = computed(() => ({
  labels: props.breakdown.map((row) => row.category),
  datasets: [{
    data: props.breakdown.map((row) => row.total),
    backgroundColor: ['#16a34a', '#22c55e', '#4ade80', '#86efac', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899', '#64748b'],
  }],
}))

function applyPreset(preset) {
  router.get(route('admin.finance.laporan'), { preset }, { preserveState: true })
}

function applyCustom() {
  router.get(route('admin.finance.laporan'), { from: customFrom.value, to: customTo.value }, { preserveState: true })
}

function exportUrl(type) {
  const query = new URLSearchParams({ from: props.period.from, to: props.period.to }).toString()
  const path = type === 'pdf' ? route('admin.finance.laporan.export-pdf') : route('admin.finance.laporan.export-excel')
  return `${path}?${query}`
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
