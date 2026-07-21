<template>
  <Head title="Peminjaman Aset" />

  <AdminLayout title="Peminjaman Aset">
    <PageHeader title="Peminjaman Aset" description="Kelola pengajuan dan pengembalian peminjaman aset.">
      <template #actions>
        <Link :href="route('admin.asset.peminjaman.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Ajukan Peminjaman</Link>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="loans.data" empty-text="Belum ada peminjaman.">
        <template #cell-asset="{ row }">{{ row.asset?.name }}</template>
        <template #cell-loan_date="{ value }">{{ formatDate(value) }}</template>
        <template #cell-return_date_planned="{ value }">{{ formatDate(value) }}</template>
        <template #cell-status="{ value }"><AppBadge :variant="statusVariant(value)">{{ statusLabel(value) }}</AppBadge></template>
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-2">
            <button v-if="row.status === 'pending'" class="text-green-600 text-sm hover:underline" @click="approve(row)">Setujui</button>
            <button v-if="row.status === 'approved'" class="text-primary-600 text-sm hover:underline" @click="openReturn(row)">Kembalikan</button>
          </div>
        </template>
      </AppTable>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="loans.links" />
    </div>

    <AppModal :show="returnTarget !== null" title="Kembalikan Aset" @close="returnTarget = null">
      <AppSelect v-model="returnForm.condition_in" label="Kondisi Saat Dikembalikan" required :options="[{ label: 'Baik', value: 'baik' }, { label: 'Cukup', value: 'cukup' }, { label: 'Rusak Ringan', value: 'rusak_ringan' }, { label: 'Rusak Berat', value: 'rusak_berat' }]" />
      <template #footer>
        <AppButton variant="secondary" @click="returnTarget = null">Batal</AppButton>
        <AppButton :loading="returnForm.processing" @click="submitReturn">Simpan</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Plus as PlusIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppTable from '@/Components/UI/AppTable.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

defineProps({
  loans: { type: Object, required: true },
})

const columns = [
  { key: 'asset', label: 'Aset' },
  { key: 'borrower_name', label: 'Peminjam' },
  { key: 'loan_date', label: 'Tgl Pinjam' },
  { key: 'return_date_planned', label: 'Rencana Kembali' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '' },
]

const returnTarget = ref(null)
const returnForm = useForm({ condition_in: 'baik' })

function approve(loan) {
  router.post(route('admin.asset.peminjaman.approve', loan.id), {}, { preserveScroll: true })
}

function openReturn(loan) {
  returnTarget.value = loan
  returnForm.condition_in = 'baik'
}

function submitReturn() {
  returnForm.post(route('admin.asset.peminjaman.return', returnTarget.value.id), {
    preserveScroll: true,
    onSuccess: () => { returnTarget.value = null },
  })
}

function statusLabel(value) {
  return { pending: 'Pending', approved: 'Disetujui', active: 'Aktif', returned: 'Dikembalikan', overdue: 'Terlambat' }[value] ?? value
}

function statusVariant(value) {
  return { pending: 'yellow', approved: 'blue', active: 'blue', returned: 'green', overdue: 'red' }[value] ?? 'gray'
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
