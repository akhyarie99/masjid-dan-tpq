<template>
  <Head title="Pengumuman" />

  <AdminLayout title="Pengumuman">
    <PageHeader title="Pengumuman" description="Kelola pengumuman untuk portal publik dan jamaah.">
      <template v-if="can('announcement.manage')" #actions>
        <Link :href="route('pengumuman.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Buat Pengumuman</Link>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="announcements.data" empty-text="Belum ada pengumuman.">
        <template #cell-type="{ value }"><AppBadge :variant="typeVariant(value)">{{ typeLabel(value) }}</AppBadge></template>
        <template #cell-is_published="{ value }"><AppBadge :variant="value ? 'green' : 'gray'">{{ value ? 'Terbit' : 'Draft' }}</AppBadge></template>
        <template #cell-created_at="{ value }">{{ formatDate(value) }}</template>
        <template #cell-actions="{ row }">
          <div v-if="can('announcement.manage')" class="flex items-center gap-2">
            <Link :href="route('pengumuman.edit', row.id)" class="text-primary-600 text-sm hover:underline">Edit</Link>
            <button class="text-red-500 text-sm hover:underline" @click="deleteTarget = row">Hapus</button>
          </div>
        </template>
      </AppTable>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="announcements.links" />
    </div>

    <ConfirmDialog
      :show="deleteTarget !== null"
      title="Hapus Pengumuman"
      :message="`Yakin ingin menghapus '${deleteTarget?.title}'?`"
      danger
      :loading="deleting"
      @cancel="deleteTarget = null"
      @confirm="destroy"
    />
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Plus as PlusIcon } from 'lucide-vue-next'
import { usePermission } from '@/composables/usePermission'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppTable from '@/Components/UI/AppTable.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'
import ConfirmDialog from '@/Components/Shared/ConfirmDialog.vue'

defineProps({
  announcements: { type: Object, required: true },
})

const { can } = usePermission()

const columns = [
  { key: 'title', label: 'Judul' },
  { key: 'type', label: 'Tipe' },
  { key: 'is_published', label: 'Status' },
  { key: 'created_at', label: 'Dibuat' },
  { key: 'actions', label: '' },
]

const deleteTarget = ref(null)
const deleting = ref(false)

function destroy() {
  deleting.value = true
  router.delete(route('pengumuman.destroy', deleteTarget.value.id), {
    preserveScroll: true,
    onFinish: () => { deleting.value = false; deleteTarget.value = null },
  })
}

function typeLabel(type) {
  return { umum: 'Umum', kegiatan: 'Kegiatan', duka: 'Duka Cita', urgent: 'Penting' }[type] ?? type
}

function typeVariant(type) {
  return { umum: 'gray', kegiatan: 'blue', duka: 'gray', urgent: 'red' }[type] ?? 'gray'
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
