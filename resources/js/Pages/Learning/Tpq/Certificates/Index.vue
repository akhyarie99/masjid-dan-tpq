<template>
  <Head title="Sertifikat" />

  <AdminLayout title="Sertifikat">
    <TpqSubNav />
    <PageHeader title="Sertifikat" description="Kelola sertifikat khatam, tahfidz, dan ijazah santri.">
      <template #actions>
        <Link :href="route('admin.tpq.sertifikat.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Terbitkan Sertifikat</Link>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="certificates.data" empty-text="Belum ada sertifikat.">
        <template #cell-student="{ row }">{{ row.student?.name }}</template>
        <template #cell-type="{ value }">{{ typeLabel(value) }}</template>
        <template #cell-issued_date="{ value }">{{ formatDate(value) }}</template>
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-2">
            <a v-if="row.pdf_path" :href="`/storage/${row.pdf_path}`" target="_blank" class="text-primary-600 text-sm hover:underline">Lihat PDF</a>
            <button class="text-red-500 text-sm hover:underline" @click="destroy(row)">Hapus</button>
          </div>
        </template>
      </AppTable>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="certificates.links" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Plus as PlusIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import TpqSubNav from '@/Components/Shared/TpqSubNav.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppTable from '@/Components/UI/AppTable.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'

defineProps({
  certificates: { type: Object, required: true },
})

const columns = [
  { key: 'certificate_number', label: 'No. Sertifikat' },
  { key: 'student', label: 'Santri' },
  { key: 'type', label: 'Jenis' },
  { key: 'achievement', label: 'Pencapaian' },
  { key: 'issued_date', label: 'Tanggal Terbit' },
  { key: 'actions', label: '' },
]

function destroy(certificate) {
  if (confirm(`Hapus sertifikat ${certificate.certificate_number}?`)) {
    router.delete(route('admin.tpq.sertifikat.destroy', certificate.id), { preserveScroll: true })
  }
}

function typeLabel(type) {
  return { khatam_iqra: 'Khatam Iqra', khatam_quran: 'Khatam Quran', tahfidz: 'Tahfidz', ijazah: 'Ijazah' }[type] ?? type
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
