<template>
  <Head title="Transaksi" />

  <AdminLayout title="Transaksi">
    <PageHeader title="Transaksi Keuangan" description="Catat dan kelola seluruh transaksi kas masjid.">
      <template #actions>
        <a :href="exportUrl('excel')" class="btn-secondary"><FileSpreadsheetIcon class="w-4 h-4" /> Excel</a>
        <a :href="exportUrl('pdf')" class="btn-secondary"><FileTextIcon class="w-4 h-4" /> PDF</a>
        <Link :href="route('admin.finance.transaksi.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Transaksi</Link>
      </template>
    </PageHeader>

    <!-- Sticky totals -->
    <div class="grid grid-cols-2 gap-4 mb-6">
      <div class="card p-4">
        <p class="text-sm text-[var(--text-muted)]">Total Pemasukan (sesuai filter)</p>
        <p class="text-xl font-bold text-green-600 mt-1">{{ formatCurrency(totals.income) }}</p>
      </div>
      <div class="card p-4">
        <p class="text-sm text-[var(--text-muted)]">Total Pengeluaran (sesuai filter)</p>
        <p class="text-xl font-bold text-red-500 mt-1">{{ formatCurrency(totals.expense) }}</p>
      </div>
    </div>

    <!-- Filters -->
    <AppCard class="mb-6">
      <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <AppSelect v-model="form.type" placeholder="Semua Tipe" :options="[{ label: 'Pemasukan', value: 'income' }, { label: 'Pengeluaran', value: 'expense' }]" />
        <AppSelect v-model="form.category_id" placeholder="Semua Kategori" :options="categories.map((c) => ({ label: c.name, value: c.id }))" />
        <AppSelect v-model="form.kas_account_id" placeholder="Semua Rekening" :options="kasAccounts.map((k) => ({ label: k.name, value: k.id }))" />
        <AppSelect v-model="form.status" placeholder="Semua Status" :options="[{ label: 'Pending', value: 'pending' }, { label: 'Disetujui', value: 'approved' }, { label: 'Ditolak', value: 'rejected' }]" />
        <AppInput v-model="form.from" type="date" placeholder="Dari" />
        <AppInput v-model="form.to" type="date" placeholder="Sampai" />
      </div>
    </AppCard>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>No. Ref</th><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Rekening</th><th>Nominal</th><th>Status</th><th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="transactions.data.length === 0">
              <td colspan="8" class="text-center text-[var(--text-muted)] py-8">Tidak ada transaksi.</td>
            </tr>
            <tr v-for="tx in transactions.data" :key="tx.id">
              <td>{{ tx.reference_number }}</td>
              <td>{{ formatDate(tx.transaction_date) }}</td>
              <td class="max-w-[220px] truncate">{{ tx.description }}</td>
              <td>{{ tx.category?.name }}</td>
              <td>{{ tx.kas_account?.name }}</td>
              <td :class="tx.type === 'income' ? 'text-green-600' : 'text-red-500'" class="font-medium whitespace-nowrap">
                {{ tx.type === 'income' ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
              </td>
              <td><AppBadge :variant="statusVariant(tx.status)">{{ statusLabel(tx.status) }}</AppBadge></td>
              <td>
                <div class="flex items-center gap-2">
                  <template v-if="tx.status === 'pending' && can('finance.approve')">
                    <button class="text-green-600 text-sm hover:underline" @click="approve(tx, 'approved')">Setuju</button>
                    <button class="text-red-500 text-sm hover:underline" @click="approve(tx, 'rejected')">Tolak</button>
                  </template>
                  <Link :href="route('admin.finance.transaksi.edit', tx.id)" class="text-primary-600 text-sm hover:underline">Edit</Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="transactions.links" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Plus as PlusIcon, FileText as FileTextIcon, FileSpreadsheet as FileSpreadsheetIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'
import { usePermission } from '@/composables/usePermission'

const props = defineProps({
  transactions: { type: Object, required: true },
  totals: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  kasAccounts: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

const { can } = usePermission()

const form = reactive({
  type: props.filters.type ?? '',
  category_id: props.filters.category_id ?? '',
  kas_account_id: props.filters.kas_account_id ?? '',
  status: props.filters.status ?? '',
  from: props.filters.from ?? '',
  to: props.filters.to ?? '',
})

watch(form, () => {
  router.get(route('admin.finance.transaksi.index'), { ...form }, { preserveState: true, replace: true })
}, { deep: true })

function exportUrl(type) {
  const query = new URLSearchParams({ ...form }).toString()
  const path = type === 'pdf' ? route('admin.finance.laporan.export-pdf') : route('admin.finance.laporan.export-excel')
  return query ? `${path}?${query}` : path
}

function approve(tx, status) {
  router.post(route('admin.finance.transaksi.approve', tx.id), { status }, { preserveScroll: true })
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}

function statusLabel(status) {
  return { pending: 'Pending', approved: 'Disetujui', rejected: 'Ditolak' }[status] ?? status
}

function statusVariant(status) {
  return { pending: 'yellow', approved: 'green', rejected: 'red' }[status] ?? 'gray'
}
</script>
