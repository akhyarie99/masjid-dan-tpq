<template>
  <Head title="Peminjaman Buku" />

  <AdminLayout title="Peminjaman Buku">
    <PageHeader title="Peminjaman Buku" description="Kelola peminjaman dan pengembalian buku perpustakaan.">
      <template #actions>
        <Link :href="route('admin.library.index')" class="btn-secondary">Katalog</Link>
        <AppButton v-if="can('library.manage')" @click="showModal = true"><PlusIcon class="w-4 h-4" /> Pinjam Buku</AppButton>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Buku</th><th>Peminjam</th><th>Tgl Pinjam</th><th>Rencana Kembali</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <tr v-if="loans.data.length === 0">
              <td colspan="6" class="text-center text-[var(--text-muted)] py-8">Belum ada peminjaman.</td>
            </tr>
            <tr v-for="loan in loans.data" :key="loan.id">
              <td>{{ loan.book?.title }}</td>
              <td>{{ loan.borrower_name }} <span class="text-xs text-[var(--text-muted)]">({{ loan.borrower_phone }})</span></td>
              <td>{{ formatDate(loan.loan_date) }}</td>
              <td>{{ formatDate(loan.return_date_planned) }}</td>
              <td><AppBadge :variant="statusVariant(loan.status)">{{ statusLabel(loan.status) }}</AppBadge></td>
              <td>
                <button v-if="loan.status === 'dipinjam' && can('library.manage')" class="text-primary-600 text-sm hover:underline" @click="returnBook(loan)">Kembalikan</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="loans.links" />
    </div>

    <AppModal :show="showModal" title="Pinjam Buku" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="submit">
        <AppSelect v-model="form.book_id" label="Buku" required :options="books.map((b) => ({ label: `${b.title} (tersedia: ${b.available})`, value: b.id }))" :error="form.errors.book_id" />
        <AppInput v-model="form.borrower_name" label="Nama Peminjam" required :error="form.errors.borrower_name" />
        <AppInput v-model="form.borrower_phone" label="No. HP" required :error="form.errors.borrower_phone" />
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.loan_date" type="date" label="Tanggal Pinjam" required :error="form.errors.loan_date" />
          <AppInput v-model="form.return_date_planned" type="date" label="Rencana Kembali" required :error="form.errors.return_date_planned" />
        </div>
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showModal = false">Batal</AppButton>
        <AppButton :loading="form.processing" @click="submit">Pinjam</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Plus as PlusIcon } from 'lucide-vue-next'
import { usePermission } from '@/composables/usePermission'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

defineProps({
  loans: { type: Object, required: true },
  books: { type: Array, default: () => [] },
})

const { can } = usePermission()

const showModal = ref(false)
const form = useForm({ book_id: '', borrower_name: '', borrower_phone: '', loan_date: new Date().toISOString().slice(0, 10), return_date_planned: '' })

function submit() {
  form.post(route('admin.library.loans.store'), { preserveScroll: true, onSuccess: () => { showModal.value = false; form.reset() } })
}

function returnBook(loan) {
  router.post(route('admin.library.loans.return', loan.id), {}, { preserveScroll: true })
}

function statusLabel(status) {
  return { dipinjam: 'Dipinjam', dikembalikan: 'Dikembalikan', terlambat: 'Terlambat' }[status] ?? status
}

function statusVariant(status) {
  return { dipinjam: 'blue', dikembalikan: 'green', terlambat: 'red' }[status] ?? 'gray'
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
