<template>
  <Head title="Manajemen Pengguna" />

  <AdminLayout title="Manajemen Pengguna">
    <PageHeader title="Manajemen Pengguna" description="Kelola akun pengurus masjid dan hak akses mereka.">
      <template #actions>
        <Link :href="route('admin.settings.pengguna.create')" class="btn-primary">
          <PlusIcon class="w-4 h-4" /> Tambah Pengguna
        </Link>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="users.data" empty-text="Belum ada pengguna.">
        <template #cell-name="{ row }">
          <div>
            <p class="font-medium text-[var(--text-primary)]">{{ row.name }}</p>
            <p class="text-xs text-[var(--text-muted)]">{{ row.phone }}</p>
          </div>
        </template>
        <template #cell-roles="{ row }">
          <AppBadge variant="blue">{{ row.roles?.[0]?.name ?? '-' }}</AppBadge>
        </template>
        <template #cell-is_active="{ value }">
          <AppBadge :variant="value ? 'green' : 'gray'">{{ value ? 'Aktif' : 'Nonaktif' }}</AppBadge>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-2">
            <Link :href="route('admin.settings.pengguna.edit', row.id)" class="text-primary-600 hover:underline text-sm">Edit</Link>
            <button type="button" class="text-red-500 hover:underline text-sm" @click="confirmDelete(row)">Hapus</button>
          </div>
        </template>
      </AppTable>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="users.links" />
    </div>

    <ConfirmDialog
      :show="deleteTarget !== null"
      title="Hapus Pengguna"
      :message="`Yakin ingin menghapus ${deleteTarget?.name}?`"
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
import { Plus as PlusIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppTable from '@/Components/UI/AppTable.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'
import ConfirmDialog from '@/Components/Shared/ConfirmDialog.vue'

defineProps({
  users: { type: Object, required: true },
  roles: { type: Array, default: () => [] },
})

const columns = [
  { key: 'name', label: 'Pengguna' },
  { key: 'roles', label: 'Peran' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: '' },
]

const deleteTarget = ref(null)
const deleting = ref(false)

function confirmDelete(user) {
  deleteTarget.value = user
}

function destroy() {
  deleting.value = true
  router.delete(route('admin.settings.pengguna.destroy', deleteTarget.value.id), {
    preserveScroll: true,
    onFinish: () => {
      deleting.value = false
      deleteTarget.value = null
    },
  })
}
</script>
