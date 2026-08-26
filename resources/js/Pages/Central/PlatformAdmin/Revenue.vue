<template>
  <Head title="Superadmin - Pendapatan" />

  <PlatformAdminLayout>
    <h1 class="text-xl font-bold text-[var(--text-primary)] mb-4">Rekapan Pendapatan</h1>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="card p-4">
        <p class="text-xs text-[var(--text-muted)]">Bulan Ini</p>
        <p class="text-lg font-bold text-[var(--text-primary)] mt-1">{{ formatRupiah(summary.this_month) }}</p>
      </div>
      <div class="card p-4">
        <p class="text-xs text-[var(--text-muted)]">Tahun Ini</p>
        <p class="text-lg font-bold text-[var(--text-primary)] mt-1">{{ formatRupiah(summary.this_year) }}</p>
      </div>
      <div class="card p-4">
        <p class="text-xs text-[var(--text-muted)]">Sepanjang Waktu</p>
        <p class="text-lg font-bold text-[var(--text-primary)] mt-1">{{ formatRupiah(summary.all_time) }}</p>
      </div>
      <div class="card p-4">
        <p class="text-xs text-[var(--text-muted)]">Belum Bayar Bulan Ini</p>
        <p class="text-lg font-bold mt-1" :class="summary.unpaid_count > 0 ? 'text-red-500' : 'text-green-600'">
          {{ summary.unpaid_count }} tenant
        </p>
      </div>
    </div>

    <div class="card p-5 mb-6">
      <p class="text-sm font-medium text-[var(--text-primary)] mb-3">Tarif Default Platform</p>
      <form class="flex items-end gap-2" @submit.prevent="submitDefaultFee">
        <div class="flex-1 max-w-xs">
          <AppInput v-model="defaultFeeForm.default_monthly_fee" type="number" label="Rp/bulan" :error="defaultFeeForm.errors.default_monthly_fee" />
        </div>
        <AppButton type="submit" :loading="defaultFeeForm.processing">Simpan</AppButton>
      </form>
      <p class="text-xs text-[var(--text-muted)] mt-2">Berlaku untuk semua tenant yang belum punya tarif khusus.</p>
    </div>

    <div class="card overflow-x-auto mb-6">
      <p class="text-sm font-medium text-[var(--text-primary)] p-4 pb-0">12 Bulan Terakhir</p>
      <table class="table w-full text-sm">
        <thead>
          <tr class="text-left text-[var(--text-muted)]">
            <th class="p-3">Periode</th>
            <th class="p-3">Jumlah Tenant Bayar</th>
            <th class="p-3">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="monthlyTotals.length === 0">
            <td colspan="3" class="p-4 text-center text-[var(--text-muted)]">Belum ada data pembayaran.</td>
          </tr>
          <tr v-for="row in monthlyTotals" :key="`${row.year}-${row.month}`" class="border-t border-[var(--border)]">
            <td class="p-3">{{ monthLabel(row.month) }} {{ row.year }}</td>
            <td class="p-3">{{ row.tenant_count }}</td>
            <td class="p-3 font-medium">{{ formatRupiah(row.total) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card overflow-x-auto">
      <p class="text-sm font-medium text-[var(--text-primary)] p-4 pb-0">Status Tenant Bulan Ini</p>
      <table class="table w-full text-sm">
        <thead>
          <tr class="text-left text-[var(--text-muted)]">
            <th class="p-3">Nama</th>
            <th class="p-3">Tarif</th>
            <th class="p-3">Status Bulan Ini</th>
            <th class="p-3">Total Bayar (Sepanjang Waktu)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="tenant in tenantBreakdown" :key="tenant.id" class="border-t border-[var(--border)]">
            <td class="p-3 font-medium text-[var(--text-primary)]">
              <Link :href="route('platform-admin.tenant.show', tenant.id)" class="hover:underline">{{ tenant.name }}</Link>
            </td>
            <td class="p-3">{{ formatRupiah(tenant.effective_fee) }}</td>
            <td class="p-3">
              <span :class="tenant.paid_this_month ? 'text-green-600' : 'text-red-500'" class="font-medium">
                {{ tenant.paid_this_month ? 'Lunas' : 'Belum bayar' }}
              </span>
            </td>
            <td class="p-3">{{ formatRupiah(tenant.total_paid) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </PlatformAdminLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  summary: { type: Object, required: true },
  monthlyTotals: { type: Array, default: () => [] },
  tenantBreakdown: { type: Array, default: () => [] },
  defaultMonthlyFee: { type: Number, default: 0 },
})

const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']

function monthLabel(month) {
  return monthNames[month - 1] ?? month
}

function formatRupiah(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value ?? 0)
}

const defaultFeeForm = useForm({
  default_monthly_fee: props.defaultMonthlyFee || '',
})

function submitDefaultFee() {
  defaultFeeForm.put(route('platform-admin.settings.fee'), { preserveScroll: true })
}
</script>
