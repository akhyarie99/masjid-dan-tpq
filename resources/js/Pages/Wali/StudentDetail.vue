<template>
  <Head :title="student.name" />

  <WaliLayout>
    <Link :href="route('wali.dashboard')" class="inline-flex items-center gap-1 text-sm text-[var(--text-muted)] hover:text-[var(--text-primary)] mb-4">
      <ArrowLeftIcon class="w-4 h-4" /> Kembali
    </Link>

    <div class="card p-5 mb-4">
      <div class="flex items-center gap-3">
        <div class="w-14 h-14 rounded-full bg-[var(--bg-muted)] flex items-center justify-center text-2xl overflow-hidden shrink-0">
          <img v-if="student.photo" :src="student.photo" class="w-full h-full object-cover" alt="" />
          <span v-else>🧒</span>
        </div>
        <div>
          <p class="text-lg font-bold text-[var(--text-primary)]">{{ student.name }}</p>
          <p class="text-sm text-[var(--text-muted)]">NIS {{ student.nis }}</p>
        </div>
      </div>
    </div>

    <div class="card p-5 mb-4">
      <p class="text-sm font-medium text-[var(--text-primary)] mb-2">Progres Mengaji Terbaru</p>
      <EmptyState v-if="dailyProgress.length === 0" title="Belum ada catatan mengaji." />
      <ul v-else class="space-y-2">
        <li v-for="(entry, index) in dailyProgress" :key="index" class="text-sm border-b border-[var(--border)] last:border-0 pb-2 last:pb-0">
          <div class="flex items-center justify-between">
            <span class="text-[var(--text-primary)] font-medium">{{ formatDate(entry.date) }}</span>
            <span
              class="text-xs font-medium px-2 py-0.5 rounded-full"
              :class="entry.keterangan === 'lancar' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300'"
            >
              {{ entry.keterangan === 'lancar' ? 'Lancar' : 'Ulang' }}
            </span>
          </div>
          <p class="text-[var(--text-muted)] mt-0.5">{{ entry.summary }}</p>
          <p v-if="entry.catatan" class="text-xs text-[var(--text-muted)] italic mt-0.5">"{{ entry.catatan }}"</p>
        </li>
      </ul>
    </div>

    <div class="card p-5 mb-4">
      <p class="text-sm font-medium text-[var(--text-primary)] mb-2">Raport</p>
      <EmptyState v-if="reportCards.length === 0" title="Belum ada raport tersedia." />
      <ul v-else class="space-y-2">
        <li v-for="reportCard in reportCards" :key="reportCard.id" class="flex items-center justify-between text-sm">
          <span>{{ reportCard.semester?.name }}</span>
          <Link :href="route('wali.reportcard', reportCard.id)" class="text-primary-600 hover:underline">Lihat Raport</Link>
        </li>
      </ul>
    </div>

    <div class="card p-5">
      <p class="text-sm font-medium text-[var(--text-primary)] mb-2">Riwayat Infaq</p>
      <EmptyState v-if="sppBills.length === 0" title="Belum ada tagihan Infaq." />
      <ul v-else class="space-y-3">
        <li v-for="bill in sppBills" :key="bill.id" class="text-sm border-b border-[var(--border)] last:border-0 pb-3 last:pb-0">
          <div class="flex items-center justify-between">
            <span class="text-[var(--text-primary)] font-medium">{{ monthLabel(bill.month, bill.year) }}</span>
            <span
              class="text-xs font-medium px-2 py-0.5 rounded-full"
              :class="badgeClass(bill)"
            >
              {{ badgeLabel(bill) }}
            </span>
          </div>
          <p class="text-[var(--text-muted)] mt-0.5">{{ formatCurrency(bill.amount) }}</p>

          <p v-if="bill.proof_status === 'rejected'" class="text-xs text-red-500 mt-1">
            Ditolak: {{ bill.proof_rejection_reason }}
          </p>

          <div v-if="bill.status !== 'paid' && bill.proof_status !== 'pending'" class="mt-2">
            <button
              type="button"
              class="text-xs font-medium text-primary-600 hover:underline"
              @click="openUpload(bill)"
            >
              Kirim Bukti Transfer
            </button>
          </div>
          <p v-else-if="bill.proof_status === 'pending'" class="text-xs text-yellow-600 mt-1">
            Bukti terkirim, menunggu konfirmasi admin.
          </p>
        </li>
      </ul>
    </div>

    <AppModal :show="uploadTarget !== null" title="Kirim Bukti Transfer" @close="uploadTarget = null">
      <form v-if="uploadTarget" class="space-y-3" @submit.prevent="submitProof">
        <p class="text-sm text-[var(--text-muted)]">
          Infaq {{ monthLabel(uploadTarget.month, uploadTarget.year) }} — {{ formatCurrency(uploadTarget.amount - uploadTarget.paid_amount) }}
        </p>
        <input type="file" accept="image/jpeg,image/png,application/pdf" class="input" @input="proofForm.proof_file = $event.target.files[0]" />
        <p v-if="proofForm.errors.proof_file" class="text-xs text-red-500">{{ proofForm.errors.proof_file }}</p>
        <p class="text-xs text-[var(--text-muted)]">Foto/scan bukti transfer (JPG/PNG/PDF, maks 5MB).</p>
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="uploadTarget = null">Batal</AppButton>
        <AppButton :loading="proofForm.processing" @click="submitProof">Kirim</AppButton>
      </template>
    </AppModal>
  </WaliLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft as ArrowLeftIcon } from 'lucide-vue-next'
import dayjs from 'dayjs'
import WaliLayout from '@/Layouts/WaliLayout.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  student: { type: Object, required: true },
  dailyProgress: { type: Array, default: () => [] },
  reportCards: { type: Array, default: () => [] },
  sppBills: { type: Array, default: () => [] },
})

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}

function monthLabel(month, year) {
  return dayjs(`${year}-${String(month).padStart(2, '0')}-01`).format('MMMM YYYY')
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

function badgeLabel(bill) {
  if (bill.proof_status === 'pending') return 'Menunggu Konfirmasi'
  if (bill.proof_status === 'rejected') return 'Bukti Ditolak'
  return { unpaid: 'Belum Bayar', partial: 'Cicil', paid: 'Lunas' }[bill.status] ?? bill.status
}

function badgeClass(bill) {
  if (bill.proof_status === 'pending') return 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300'
  if (bill.proof_status === 'rejected') return 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300'
  return {
    unpaid: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
    partial: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300',
    paid: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
  }[bill.status] ?? 'bg-[var(--bg-muted)] text-[var(--text-muted)]'
}

const uploadTarget = ref(null)
const proofForm = useForm({ proof_file: null })

function openUpload(bill) {
  uploadTarget.value = bill
  proofForm.reset()
}

function submitProof() {
  proofForm.post(route('wali.spp.proof.upload', uploadTarget.value.id), {
    preserveScroll: true,
    onSuccess: () => { uploadTarget.value = null },
  })
}
</script>
