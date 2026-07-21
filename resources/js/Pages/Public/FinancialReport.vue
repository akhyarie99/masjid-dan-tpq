<template>
  <Head title="Laporan Keuangan" />

  <PublicLayout>
    <div class="max-w-3xl mx-auto px-4 py-10 md:py-14">
      <h1 class="text-2xl font-bold text-[var(--text-primary)] mb-1">Laporan Keuangan</h1>
      <p class="text-sm text-[var(--text-muted)] mb-6">{{ masjid.name }} — Periode {{ formatDate(period.from) }} s/d {{ formatDate(period.to) }}</p>

      <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="card p-4 text-center">
          <p class="text-xs md:text-sm text-[var(--text-muted)]">Pemasukan</p>
          <p class="text-lg md:text-xl font-bold text-green-600 mt-1">{{ formatCurrency(summary.income) }}</p>
        </div>
        <div class="card p-4 text-center">
          <p class="text-xs md:text-sm text-[var(--text-muted)]">Pengeluaran</p>
          <p class="text-lg md:text-xl font-bold text-red-500 mt-1">{{ formatCurrency(summary.expense) }}</p>
        </div>
        <div class="card p-4 text-center">
          <p class="text-xs md:text-sm text-[var(--text-muted)]">Saldo</p>
          <p class="text-lg md:text-xl font-bold text-primary-600 mt-1">{{ formatCurrency(summary.balance) }}</p>
        </div>
      </div>

      <h2 class="text-lg font-semibold text-[var(--text-primary)] mb-3">Breakdown per Kategori</h2>
      <EmptyState v-if="breakdown.length === 0" title="Belum ada data keuangan bulan ini." />
      <div v-else class="table-responsive card !p-0">
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
    </div>
  </PublicLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

defineProps({
  masjid: { type: Object, required: true },
  period: { type: Object, required: true },
  summary: { type: Object, required: true },
  breakdown: { type: Array, default: () => [] },
})

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
