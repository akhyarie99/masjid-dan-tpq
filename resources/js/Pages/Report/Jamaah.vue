<template>
  <Head title="Laporan Jamaah" />

  <AdminLayout title="Laporan Jamaah">
    <PageHeader title="Laporan Jamaah" description="Ringkasan demografi data jamaah." />

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-primary-600">{{ summary.total }}</p>
        <p class="text-xs text-[var(--text-muted)]">Total Jamaah Terdata</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-green-600">{{ summary.byStatus?.aktif ?? 0 }}</p>
        <p class="text-xs text-[var(--text-muted)]">Aktif</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-[var(--text-primary)]">{{ Object.keys(summary.byRt || {}).length }}</p>
        <p class="text-xs text-[var(--text-muted)]">Jumlah RT Terdata</p>
      </div>
    </div>

    <AppCard title="Sebaran per RT" :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>RT</th><th>Jumlah Jamaah</th></tr></thead>
          <tbody>
            <tr v-if="Object.keys(summary.byRt || {}).length === 0">
              <td colspan="2" class="text-center text-[var(--text-muted)] py-8">Belum ada data RT.</td>
            </tr>
            <tr v-for="(count, rt) in summary.byRt" :key="rt">
              <td>RT {{ rt }}</td>
              <td>{{ count }}</td>
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
})
</script>
