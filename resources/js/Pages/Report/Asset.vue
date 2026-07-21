<template>
  <Head title="Laporan Aset" />

  <AdminLayout title="Laporan Aset">
    <PageHeader title="Laporan Aset" description="Ringkasan kondisi dan status seluruh aset masjid." />

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-primary-600">{{ summary.total }}</p>
        <p class="text-xs text-[var(--text-muted)]">Total Aset</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-green-600">{{ formatCurrency(summary.totalValue) }}</p>
        <p class="text-xs text-[var(--text-muted)]">Total Nilai Perolehan</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-[var(--text-primary)]">{{ summary.byCondition?.rusak_berat ?? 0 }}</p>
        <p class="text-xs text-[var(--text-muted)]">Rusak Berat</p>
      </div>
    </div>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Nama</th><th>Kategori</th><th>Kondisi</th><th>Status</th><th>Lokasi</th></tr></thead>
          <tbody>
            <tr v-for="asset in assets" :key="asset.id">
              <td>{{ asset.name }}</td>
              <td>{{ asset.category?.name }}</td>
              <td class="capitalize">{{ asset.condition.replace('_', ' ') }}</td>
              <td class="capitalize">{{ asset.status }}</td>
              <td>{{ asset.location }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'

defineProps({
  summary: { type: Object, required: true },
  assets: { type: Array, default: () => [] },
})

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}
</script>
