<template>
  <Head title="Donasi" />

  <AdminLayout title="Donasi">
    <PageHeader title="Donasi Digital" description="Riwayat donasi yang masuk melalui portal publik." />

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="donations.data" empty-text="Belum ada donasi.">
        <template #cell-donor_name="{ value }">{{ value ?? 'Hamba Allah (Anonim)' }}</template>
        <template #cell-amount="{ value }">{{ formatCurrency(value) }}</template>
        <template #cell-status="{ value }"><AppBadge :variant="statusVariant(value)">{{ statusLabel(value) }}</AppBadge></template>
        <template #cell-paid_at="{ value }">{{ value ? formatDate(value) : '-' }}</template>
        <template #cell-actions="{ row }">
          <Link :href="route('admin.finance.donasi.show', row.id)" class="text-primary-600 text-sm hover:underline">Detail</Link>
        </template>
      </AppTable>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="donations.links" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppTable from '@/Components/UI/AppTable.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'

defineProps({
  donations: { type: Object, required: true },
})

const columns = [
  { key: 'donor_name', label: 'Donatur' },
  { key: 'purpose', label: 'Tujuan' },
  { key: 'amount', label: 'Nominal' },
  { key: 'payment_method', label: 'Metode' },
  { key: 'status', label: 'Status' },
  { key: 'paid_at', label: 'Dibayar' },
  { key: 'actions', label: '' },
]

function statusLabel(status) {
  return { pending: 'Pending', paid: 'Lunas', failed: 'Gagal', expired: 'Kedaluwarsa' }[status] ?? status
}

function statusVariant(status) {
  return { pending: 'yellow', paid: 'green', failed: 'red', expired: 'gray' }[status] ?? 'gray'
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY, HH:mm')
}
</script>
