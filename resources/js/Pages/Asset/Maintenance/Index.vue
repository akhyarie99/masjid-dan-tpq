<template>
  <Head title="Maintenance Aset" />

  <AdminLayout title="Maintenance Aset">
    <PageHeader title="Maintenance Aset" description="Jadwal perawatan, perbaikan, dan inspeksi aset.">
      <template #actions>
        <Link :href="route('admin.asset.maintenance.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Jadwalkan</Link>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="maintenances.data" empty-text="Belum ada jadwal maintenance.">
        <template #cell-asset="{ row }">{{ row.asset?.name }} <span class="text-xs text-[var(--text-muted)]">({{ row.asset?.asset_code }})</span></template>
        <template #cell-type="{ value }">{{ typeLabel(value) }}</template>
        <template #cell-status="{ value }"><AppBadge :variant="statusVariant(value)">{{ statusLabel(value) }}</AppBadge></template>
        <template #cell-scheduled_date="{ value }">{{ formatDate(value) }}</template>
        <template #cell-actions="{ row }">
          <Link :href="route('admin.asset.maintenance.edit', row.id)" class="text-primary-600 text-sm hover:underline">Kelola</Link>
        </template>
      </AppTable>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="maintenances.links" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Plus as PlusIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppTable from '@/Components/UI/AppTable.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'

defineProps({
  maintenances: { type: Object, required: true },
})

const columns = [
  { key: 'asset', label: 'Aset' },
  { key: 'type', label: 'Tipe' },
  { key: 'scheduled_date', label: 'Tanggal' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '' },
]

function typeLabel(value) {
  return { scheduled: 'Rutin', repair: 'Perbaikan', inspection: 'Inspeksi' }[value] ?? value
}

function statusLabel(value) {
  return { scheduled: 'Dijadwalkan', in_progress: 'Diproses', done: 'Selesai' }[value] ?? value
}

function statusVariant(value) {
  return { scheduled: 'yellow', in_progress: 'blue', done: 'green' }[value] ?? 'gray'
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
