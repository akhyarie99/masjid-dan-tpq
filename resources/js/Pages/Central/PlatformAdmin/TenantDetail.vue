<template>
  <Head :title="`Tenant - ${tenant.name}`" />

  <PlatformAdminLayout>
    <div class="flex items-center gap-3 mb-4">
      <Link :href="route('platform-admin.dashboard')" class="btn-secondary text-xs">&larr; Kembali</Link>
      <h1 class="text-xl font-bold text-[var(--text-primary)]">{{ tenant.name }}</h1>
      <span :class="tenant.is_active ? 'text-green-600' : 'text-red-500'" class="text-sm font-medium">
        {{ tenant.is_active ? 'Aktif' : 'Nonaktif' }}
      </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div class="card p-5 space-y-5">
        <div>
          <p class="text-sm font-medium text-[var(--text-primary)] mb-3">Tarif Langganan</p>
          <form class="flex items-end gap-2" @submit.prevent="submitFee">
            <div class="flex-1">
              <AppInput v-model="feeForm.monthly_fee" type="number" label="Tarif Khusus (Rp/bulan)" :error="feeForm.errors.monthly_fee"
                :placeholder="`Kosongkan = pakai default (${formatRupiah(defaultMonthlyFee)})`" />
            </div>
            <AppButton type="submit" :loading="feeForm.processing">Simpan</AppButton>
          </form>
          <p class="text-xs text-[var(--text-muted)] mt-2">Tarif aktif saat ini: <strong>{{ formatRupiah(tenant.effective_fee) }}</strong>/bulan</p>
        </div>

        <div class="pt-4 border-t border-[var(--border)]">
          <p class="text-sm font-medium text-[var(--text-primary)] mb-3">Masa Aktif</p>
          <form class="flex items-end gap-2" @submit.prevent="submitActiveUntil">
            <div class="flex-1">
              <AppInput v-model="activeUntilForm.active_until" type="date" label="Aktif Hingga" :error="activeUntilForm.errors.active_until" />
            </div>
            <AppButton type="submit" :loading="activeUntilForm.processing">Simpan</AppButton>
          </form>
          <p v-if="tenant.is_expired" class="text-xs text-red-500 mt-2 font-medium">
            Sudah lewat masa aktif — nonaktifkan tenant secara manual lewat tombol di daftar tenant kalau perlu.
          </p>
          <p v-else class="text-xs text-[var(--text-muted)] mt-2">
            Cuma penanda di panel ini, tidak otomatis menonaktifkan tenant.
          </p>
        </div>
      </div>

      <div class="card p-5">
        <p class="text-sm font-medium text-[var(--text-primary)] mb-3">Catat Pembayaran</p>
        <form class="space-y-2" @submit.prevent="submitPayment">
          <div class="grid grid-cols-2 gap-2">
            <AppSelect v-model="paymentForm.period_month" label="Bulan" :options="monthOptions" :error="paymentForm.errors.period_month" />
            <AppInput v-model="paymentForm.period_year" type="number" label="Tahun" :error="paymentForm.errors.period_year" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <AppInput v-model="paymentForm.amount" type="number" label="Jumlah (Rp)" :error="paymentForm.errors.amount" />
            <AppInput v-model="paymentForm.paid_at" type="date" label="Tanggal Bayar" :error="paymentForm.errors.paid_at" />
          </div>
          <AppInput v-model="paymentForm.note" label="Catatan (opsional)" :error="paymentForm.errors.note" />
          <AppButton type="submit" :loading="paymentForm.processing" class="w-full">Catat Pembayaran</AppButton>
        </form>
      </div>
    </div>

    <div class="card overflow-x-auto">
      <p class="text-sm font-medium text-[var(--text-primary)] p-4 pb-0">Riwayat Pembayaran</p>
      <table class="table w-full text-sm">
        <thead>
          <tr class="text-left text-[var(--text-muted)]">
            <th class="p-3">Periode</th>
            <th class="p-3">Jumlah</th>
            <th class="p-3">Tanggal Bayar</th>
            <th class="p-3">Catatan</th>
            <th class="p-3">Dicatat oleh</th>
            <th class="p-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="payments.length === 0">
            <td colspan="6" class="p-4 text-center text-[var(--text-muted)]">Belum ada pembayaran tercatat.</td>
          </tr>
          <tr v-for="payment in payments" :key="payment.id" class="border-t border-[var(--border)]">
            <td class="p-3">{{ monthLabel(payment.period_month) }} {{ payment.period_year }}</td>
            <td class="p-3">{{ formatRupiah(payment.amount) }}</td>
            <td class="p-3">{{ payment.paid_at }}</td>
            <td class="p-3">{{ payment.note ?? '-' }}</td>
            <td class="p-3">{{ payment.recorded_by ?? '-' }}</td>
            <td class="p-3">
              <button type="button" class="text-red-500 hover:underline text-xs" @click="destroyPayment(payment)">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </PlatformAdminLayout>
</template>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  tenant: { type: Object, required: true },
  payments: { type: Array, default: () => [] },
  defaultMonthlyFee: { type: Number, default: 0 },
})

const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
const monthOptions = monthNames.map((label, i) => ({ label, value: i + 1 }))

function monthLabel(month) {
  return monthNames[month - 1] ?? month
}

function formatRupiah(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value ?? 0)
}

const feeForm = useForm({
  monthly_fee: props.tenant.monthly_fee ?? '',
})

function submitFee() {
  feeForm.put(route('platform-admin.tenant.fee', props.tenant.id), { preserveScroll: true })
}

const activeUntilForm = useForm({
  active_until: props.tenant.active_until ?? '',
})

function submitActiveUntil() {
  activeUntilForm.put(route('platform-admin.tenant.active-until', props.tenant.id), { preserveScroll: true })
}

const now = new Date()
const paymentForm = useForm({
  amount: props.tenant.effective_fee || '',
  period_month: now.getMonth() + 1,
  period_year: now.getFullYear(),
  paid_at: now.toISOString().slice(0, 10),
  note: '',
})

function submitPayment() {
  paymentForm.post(route('platform-admin.tenant.payments.store', props.tenant.id), {
    preserveScroll: true,
    onSuccess: () => paymentForm.reset('note'),
  })
}

function destroyPayment(payment) {
  if (!confirm('Hapus catatan pembayaran ini?')) return
  router.delete(route('platform-admin.tenant.payments.destroy', [props.tenant.id, payment.id]), { preserveScroll: true })
}
</script>
