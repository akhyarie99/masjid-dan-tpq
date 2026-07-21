<template>
  <Head title="Detail Donasi" />

  <AdminLayout title="Detail Donasi">
    <PageHeader title="Detail Donasi">
      <template #actions>
        <Link :href="route('admin.finance.donasi.index')" class="btn-secondary">Kembali</Link>
      </template>
    </PageHeader>

    <AppCard class="max-w-xl">
      <dl class="divide-y divide-[var(--border)]">
        <div class="py-3 flex justify-between"><dt class="text-[var(--text-muted)]">Donatur</dt><dd class="font-medium text-[var(--text-primary)]">{{ donation.donor_name ?? 'Hamba Allah (Anonim)' }}</dd></div>
        <div class="py-3 flex justify-between"><dt class="text-[var(--text-muted)]">No. HP</dt><dd>{{ donation.donor_phone ?? '-' }}</dd></div>
        <div class="py-3 flex justify-between"><dt class="text-[var(--text-muted)]">Tujuan</dt><dd>{{ donation.purpose ?? 'Umum' }}</dd></div>
        <div class="py-3 flex justify-between"><dt class="text-[var(--text-muted)]">Nominal</dt><dd class="font-semibold text-primary-600">{{ formatCurrency(donation.amount) }}</dd></div>
        <div class="py-3 flex justify-between"><dt class="text-[var(--text-muted)]">Metode</dt><dd>{{ donation.payment_method }}</dd></div>
        <div class="py-3 flex justify-between"><dt class="text-[var(--text-muted)]">Status</dt><dd><AppBadge :variant="statusVariant(donation.status)">{{ statusLabel(donation.status) }}</AppBadge></dd></div>
        <div class="py-3 flex justify-between"><dt class="text-[var(--text-muted)]">Dibayar Pada</dt><dd>{{ donation.paid_at ? formatDate(donation.paid_at) : '-' }}</dd></div>
        <div class="py-3 flex justify-between"><dt class="text-[var(--text-muted)]">Struk WA Terkirim</dt><dd>{{ donation.receipt_sent ? 'Ya' : 'Belum' }}</dd></div>
      </dl>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'

defineProps({
  donation: { type: Object, required: true },
})

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
