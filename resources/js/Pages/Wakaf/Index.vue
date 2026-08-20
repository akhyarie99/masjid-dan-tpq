<template>
  <Head title="Wakaf" />

  <AdminLayout title="Wakaf">
    <PageHeader title="Data Wakaf" description="Kelola catatan wakaf tanah, bangunan, dan uang.">
      <template #actions>
        <Link :href="route('admin.wakaf.proyek.index')" class="btn-secondary">Proyek Pembangunan</Link>
        <Link v-if="can('wakaf.manage')" :href="route('admin.wakaf.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Catat Wakaf</Link>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Wakif</th><th>Jenis</th><th>Estimasi Nilai</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <tr v-if="records.data.length === 0">
              <td colspan="5" class="text-center text-[var(--text-muted)] py-8">Belum ada data wakaf.</td>
            </tr>
            <tr v-for="record in records.data" :key="record.id">
              <td>{{ record.wakif_name }}</td>
              <td class="capitalize">{{ record.type }}</td>
              <td>{{ record.estimated_value ? formatCurrency(record.estimated_value) : '-' }}</td>
              <td><AppBadge :variant="record.status === 'selesai' ? 'green' : 'yellow'">{{ record.status === 'selesai' ? 'Selesai' : 'Proses' }}</AppBadge></td>
              <td><Link v-if="can('wakaf.manage')" :href="route('admin.wakaf.edit', record.id)" class="text-primary-600 text-sm hover:underline">Edit</Link></td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="records.links" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Plus as PlusIcon } from 'lucide-vue-next'
import { usePermission } from '@/composables/usePermission'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'

defineProps({
  records: { type: Object, required: true },
})

const { can } = usePermission()

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}
</script>
