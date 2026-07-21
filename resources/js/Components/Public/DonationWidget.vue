<template>
  <div class="card p-6 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center gap-6">
      <div class="flex-1 text-center md:text-left">
        <p class="text-sm text-[var(--text-muted)]">Total Donasi Bulan Ini</p>
        <p class="text-3xl font-bold text-primary-600 tabular-nums mt-1">{{ formatCurrency(thisMonth) }}</p>

        <Link
          v-if="route().has('public.donation')"
          :href="route('public.donation')"
          class="btn-primary mt-4 w-full md:w-auto justify-center"
        >
          💚 Donasi / Infaq Sekarang
        </Link>

        <div v-if="bankAccounts.length" class="mt-4 space-y-1 text-sm text-[var(--text-muted)]">
          <p v-for="account in bankAccounts" :key="account.no_rekening">
            {{ account.bank }} — {{ account.no_rekening }} a.n. {{ account.atas_nama }}
          </p>
        </div>
      </div>

      <div class="flex-1">
        <p class="text-sm font-medium text-[var(--text-primary)] mb-2">Donatur Terbaru</p>
        <EmptyState v-if="latestDonors.length === 0" title="Belum ada donasi" description="Jadilah yang pertama berdonasi." />
        <ul v-else class="space-y-2">
          <li v-for="(donor, index) in latestDonors" :key="index" class="flex items-center justify-between text-sm">
            <span class="text-[var(--text-primary)]">{{ donor.donor_name ?? 'Hamba Allah' }}</span>
            <span class="font-medium text-primary-600">{{ formatCurrency(donor.amount) }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import EmptyState from '@/Components/Shared/EmptyState.vue'

defineProps({
  thisMonth: { type: Number, default: 0 },
  latestDonors: { type: Array, default: () => [] },
  bankAccounts: { type: Array, default: () => [] },
})

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}
</script>
