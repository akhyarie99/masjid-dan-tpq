<template>
  <Head title="Zakat" />

  <AdminLayout title="Zakat">
    <PageHeader title="Zakat" :description="`Tahun ${year}`">
      <template #actions>
        <Link :href="route('admin.finance.zakat.penerima.index')" class="btn-secondary">Data Penerima</Link>
        <Link :href="route('admin.finance.zakat.penerimaan.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Catat Zakat</Link>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-primary-600">{{ summary.totalMuzakki }}</p>
        <p class="text-xs text-[var(--text-muted)]">Total Muzakki</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-green-600">{{ formatCurrency(summary.totalUang) }}</p>
        <p class="text-xs text-[var(--text-muted)]">Total Zakat Uang</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-[var(--text-primary)]">{{ summary.totalBeras }} kg</p>
        <p class="text-xs text-[var(--text-muted)]">Total Zakat Beras</p>
      </div>
    </div>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Muzakki</th><th>Jenis</th><th>Jiwa</th><th>Nominal/Beras</th><th></th></tr></thead>
          <tbody>
            <tr v-if="records.length === 0">
              <td colspan="5" class="text-center text-[var(--text-muted)] py-8">Belum ada data zakat tahun ini.</td>
            </tr>
            <tr v-for="record in records" :key="record.id">
              <td>{{ record.payer_name }}</td>
              <td class="capitalize">{{ record.type }}</td>
              <td>{{ record.dependents }}</td>
              <td>{{ record.payment_type === 'uang' ? formatCurrency(record.total_amount) : `${record.rice_kg} kg` }}</td>
              <td><Link :href="route('admin.finance.zakat.penerimaan.edit', record.id)" class="text-primary-600 text-sm hover:underline">Edit</Link></td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Plus as PlusIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'

defineProps({
  year: { type: Number, required: true },
  records: { type: Array, default: () => [] },
  summary: { type: Object, required: true },
})

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}
</script>
