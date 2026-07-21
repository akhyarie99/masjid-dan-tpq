<template>
  <Head title="Anggaran (RAB)" />

  <AdminLayout title="Anggaran (RAB)">
    <PageHeader title="Anggaran (RAB)" description="Rencana Anggaran Biaya masjid per periode.">
      <template #actions>
        <Link :href="route('admin.finance.anggaran.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Buat RAB</Link>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="budgets.data" empty-text="Belum ada anggaran.">
        <template #cell-period_type="{ value }">{{ periodLabel(value) }}</template>
        <template #cell-status="{ value }"><AppBadge :variant="statusVariant(value)">{{ statusLabel(value) }}</AppBadge></template>
        <template #cell-items_sum_planned_amount="{ value }">{{ formatCurrency(value ?? 0) }}</template>
        <template #cell-actions="{ row }">
          <Link :href="route('admin.finance.anggaran.edit', row.id)" class="text-primary-600 text-sm hover:underline">Kelola</Link>
        </template>
      </AppTable>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="budgets.links" />
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
  budgets: { type: Object, required: true },
})

const columns = [
  { key: 'name', label: 'Nama' },
  { key: 'period_type', label: 'Periode' },
  { key: 'items_sum_planned_amount', label: 'Total Rencana' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '' },
]

function periodLabel(value) {
  return { monthly: 'Bulanan', yearly: 'Tahunan', project: 'Proyek' }[value] ?? value
}

function statusLabel(value) {
  return { draft: 'Draft', active: 'Aktif', closed: 'Ditutup' }[value] ?? value
}

function statusVariant(value) {
  return { draft: 'gray', active: 'green', closed: 'blue' }[value] ?? 'gray'
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}
</script>
