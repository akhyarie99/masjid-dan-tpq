<template>
  <Head title="Laporan Kegiatan" />

  <AdminLayout title="Laporan Kegiatan">
    <PageHeader title="Laporan Kegiatan" :description="`Ringkasan kegiatan tahun ${year}`" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-primary-600">{{ summary.total }}</p>
        <p class="text-xs text-[var(--text-muted)]">Total Kegiatan</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-green-600">{{ summary.totalRegistrations }}</p>
        <p class="text-xs text-[var(--text-muted)]">Total Pendaftar</p>
      </div>
    </div>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Nama</th><th>Kategori</th><th>Tanggal</th><th>Pendaftar</th></tr></thead>
          <tbody>
            <tr v-for="activity in activities" :key="activity.id">
              <td>{{ activity.name }}</td>
              <td class="capitalize">{{ activity.category?.replace('_', ' ') }}</td>
              <td>{{ formatDate(activity.start_at) }}</td>
              <td>{{ activity.registrations_count }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'

defineProps({
  year: { type: Number, required: true },
  summary: { type: Object, required: true },
  activities: { type: Array, default: () => [] },
})

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
