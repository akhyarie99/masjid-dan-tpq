<template>
  <Head title="Program Sosial" />

  <AdminLayout title="Program Sosial">
    <PageHeader title="Program Sosial" description="Kelola program bantuan sosial masjid.">
      <template #actions>
        <Link :href="route('admin.jamaah.program-sosial.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Buat Program</Link>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="programs.data" empty-text="Belum ada program sosial.">
        <template #cell-budget="{ value }">{{ formatCurrency(value) }}</template>
        <template #cell-status="{ value }"><AppBadge :variant="statusVariant(value)">{{ statusLabel(value) }}</AppBadge></template>
        <template #cell-recipients_count="{ value }">{{ value }} penerima</template>
        <template #cell-actions="{ row }">
          <Link :href="route('admin.jamaah.program-sosial.show', row.id)" class="text-primary-600 text-sm hover:underline">Kelola</Link>
        </template>
      </AppTable>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="programs.links" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Plus as PlusIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppTable from '@/Components/UI/AppTable.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'

defineProps({
  programs: { type: Object, required: true },
})

const columns = [
  { key: 'name', label: 'Program' },
  { key: 'budget', label: 'Anggaran' },
  { key: 'recipients_count', label: 'Penerima' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '' },
]

function statusLabel(status) {
  return { draft: 'Draft', active: 'Aktif', closed: 'Selesai' }[status] ?? status
}

function statusVariant(status) {
  return { draft: 'gray', active: 'green', closed: 'blue' }[status] ?? 'gray'
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}
</script>
