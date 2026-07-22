<template>
  <Head title="SPP TPQ" />

  <AdminLayout title="SPP TPQ">
    <TpqSubNav />
    <PageHeader title="SPP TPQ" :description="monthLabel">
      <template #actions>
        <AppButton variant="secondary" @click="showGenerate = true"><PlusIcon class="w-4 h-4" /> Generate Tagihan</AppButton>
        <AppButton :loading="sendingReminder" @click="sendReminders"><SendIcon class="w-4 h-4" /> Kirim Reminder</AppButton>
      </template>
    </PageHeader>

    <div class="flex items-center gap-2 mb-6">
      <AppSelect v-model="filterMonth" :options="monthOptions" class="w-40" @update:modelValue="reload" />
      <AppInput v-model.number="filterYear" type="number" class="w-28" @change="reload" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-green-600">{{ summary.paid }}</p>
        <p class="text-xs text-[var(--text-muted)]">Lunas</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-red-500">{{ summary.unpaid }}</p>
        <p class="text-xs text-[var(--text-muted)]">Belum Bayar</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-[var(--text-primary)]">{{ formatCurrency(summary.outstanding) }}</p>
        <p class="text-xs text-[var(--text-muted)]">Total Outstanding</p>
      </div>
    </div>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Nama</th><th>Nominal</th><th>Status</th><th>Tgl Bayar</th><th></th></tr></thead>
          <tbody>
            <tr v-if="bills.length === 0">
              <td colspan="5" class="text-center text-[var(--text-muted)] py-8">Belum ada tagihan bulan ini.</td>
            </tr>
            <tr v-for="bill in bills" :key="bill.id">
              <td>{{ bill.student?.name }}</td>
              <td>{{ formatCurrency(bill.amount) }}</td>
              <td><AppBadge :variant="statusVariant(bill.status)">{{ statusLabel(bill.status) }}</AppBadge></td>
              <td>{{ bill.paid_amount > 0 ? formatCurrency(bill.paid_amount) + ' dibayar' : '-' }}</td>
              <td>
                <button v-if="bill.status !== 'paid'" class="text-primary-600 text-sm hover:underline" @click="openPay(bill)">Catat Bayar</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <AppModal :show="showGenerate" title="Generate Tagihan SPP" @close="showGenerate = false">
      <form class="space-y-4" @submit.prevent="generate">
        <AppInput v-model.number="generateForm.amount" type="number" label="Nominal SPP per Santri (Rp)" required :error="generateForm.errors.amount" />
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showGenerate = false">Batal</AppButton>
        <AppButton :loading="generateForm.processing" @click="generate">Generate</AppButton>
      </template>
    </AppModal>

    <AppModal :show="payTarget !== null" title="Catat Pembayaran SPP" @close="payTarget = null">
      <form v-if="payTarget" class="space-y-4" @submit.prevent="pay">
        <p class="text-sm text-[var(--text-muted)]">{{ payTarget.student?.name }} — sisa {{ formatCurrency(payTarget.amount - payTarget.paid_amount) }}</p>
        <AppInput v-model.number="payForm.amount" type="number" label="Jumlah Dibayar (Rp)" required :error="payForm.errors.amount" />
        <AppInput v-model="payForm.paid_date" type="date" label="Tanggal Bayar" required :error="payForm.errors.paid_date" />
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="payTarget = null">Batal</AppButton>
        <AppButton :loading="payForm.processing" @click="pay">Simpan</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Plus as PlusIcon, Send as SendIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import TpqSubNav from '@/Components/Shared/TpqSubNav.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import AppModal from '@/Components/UI/AppModal.vue'

const props = defineProps({
  month: { type: Number, required: true },
  year: { type: Number, required: true },
  bills: { type: Array, default: () => [] },
  summary: { type: Object, required: true },
})

const filterMonth = ref(props.month)
const filterYear = ref(props.year)
const showGenerate = ref(false)
const payTarget = ref(null)
const sendingReminder = ref(false)

const monthOptions = Array.from({ length: 12 }, (_, i) => ({ label: dayjs().month(i).format('MMMM'), value: i + 1 }))
const monthLabel = computed(() => dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-01`).format('MMMM YYYY'))

const generateForm = useForm({ month: props.month, year: props.year, amount: 50000 })
const payForm = useForm({ amount: 0, paid_date: new Date().toISOString().slice(0, 10) })

function reload() {
  router.get(route('admin.tpq.spp.index'), { month: filterMonth.value, year: filterYear.value })
}

function generate() {
  generateForm.month = filterMonth.value
  generateForm.year = filterYear.value
  generateForm.post(route('admin.tpq.spp.generate'), { preserveScroll: true, onSuccess: () => { showGenerate.value = false } })
}

function openPay(bill) {
  payTarget.value = bill
  payForm.amount = bill.amount - bill.paid_amount
  payForm.paid_date = new Date().toISOString().slice(0, 10)
}

function pay() {
  payForm.post(route('admin.tpq.spp.pay', payTarget.value.id), { preserveScroll: true, onSuccess: () => { payTarget.value = null } })
}

function sendReminders() {
  sendingReminder.value = true
  router.post(route('admin.tpq.spp.reminders'), { month: filterMonth.value, year: filterYear.value }, { preserveScroll: true, onFinish: () => { sendingReminder.value = false } })
}

function statusLabel(status) {
  return { unpaid: 'Belum Bayar', partial: 'Cicil', paid: 'Lunas' }[status] ?? status
}

function statusVariant(status) {
  return { unpaid: 'red', partial: 'yellow', paid: 'green' }[status] ?? 'gray'
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}
</script>
