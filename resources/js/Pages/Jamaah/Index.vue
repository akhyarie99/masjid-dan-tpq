<template>
  <Head title="Database Jamaah" />

  <AdminLayout title="Database Jamaah">
    <PageHeader title="Database Jamaah" description="Kelola data jamaah masjid.">
      <template #actions>
        <a :href="route('admin.jamaah.import-template')" class="btn-secondary"><DownloadIcon class="w-4 h-4" /> Template</a>
        <button class="btn-secondary" @click="showImport = true"><UploadIcon class="w-4 h-4" /> Import Excel</button>
        <Link :href="route('admin.jamaah.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Tambah Jamaah</Link>
      </template>
    </PageHeader>

    <AppCard class="mb-6">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <AppInput v-model="filters.search" placeholder="Cari nama/HP..." />
        <AppSelect v-model="filters.status" placeholder="Semua Status" :options="[{ label: 'Aktif', value: 'aktif' }, { label: 'Luar', value: 'luar' }, { label: 'Musafir', value: 'musafir' }]" />
        <AppInput v-model="filters.rt" placeholder="RT" />
        <AppInput v-model="filters.rw" placeholder="RW" />
      </div>
    </AppCard>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th></th><th>Nama</th><th>RT/RW</th><th>Status</th><th>Tag</th><th></th></tr></thead>
          <tbody>
            <tr v-if="jamaah.data.length === 0">
              <td colspan="6" class="text-center text-[var(--text-muted)] py-8">Belum ada data jamaah.</td>
            </tr>
            <tr v-for="person in jamaah.data" :key="person.id">
              <td>
                <div class="w-8 h-8 rounded-full bg-[var(--bg-muted)] flex items-center justify-center text-xs overflow-hidden">
                  <img v-if="person.photo" :src="person.photo" class="w-full h-full object-cover" alt="" />
                  <span v-else>👤</span>
                </div>
              </td>
              <td>{{ person.name }} <span class="text-xs text-[var(--text-muted)]">({{ person.phone }})</span></td>
              <td>{{ person.rt ?? '-' }}/{{ person.rw ?? '-' }}</td>
              <td><AppBadge :variant="statusVariant(person.status)">{{ statusLabel(person.status) }}</AppBadge></td>
              <td>
                <div class="flex flex-wrap gap-1">
                  <AppBadge v-for="tag in person.tags ?? []" :key="tag" variant="blue">{{ tag }}</AppBadge>
                </div>
              </td>
              <td>
                <div class="flex items-center gap-2">
                  <Link :href="route('admin.jamaah.edit', person.id)" class="text-primary-600 text-sm hover:underline">Edit</Link>
                  <a :href="route('admin.jamaah.card', person.id)" target="_blank" class="text-primary-600 text-sm hover:underline">Kartu</a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="jamaah.links" />
    </div>

    <AppModal :show="showImport" title="Import Data Jamaah" @close="showImport = false">
      <form class="space-y-4" @submit.prevent="submitImport">
        <p class="text-sm text-[var(--text-muted)]">Unggah file Excel/CSV sesuai template.</p>
        <input type="file" class="input" accept=".csv,.xlsx,.xls" @change="importForm.file = $event.target.files[0]" />
        <p v-if="importForm.errors.file" class="text-xs text-red-500">{{ importForm.errors.file }}</p>
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showImport = false">Batal</AppButton>
        <AppButton :loading="importForm.processing" @click="submitImport">Import</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { Plus as PlusIcon, Download as DownloadIcon, Upload as UploadIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  jamaah: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
})

const filters = reactive({
  search: props.filters.search ?? '',
  status: props.filters.status ?? '',
  rt: props.filters.rt ?? '',
  rw: props.filters.rw ?? '',
})

watch(filters, () => {
  router.get(route('admin.jamaah.index'), { ...filters }, { preserveState: true, replace: true })
}, { deep: true })

const showImport = ref(false)
const importForm = useForm({ file: null })

function submitImport() {
  importForm.post(route('admin.jamaah.import'), {
    preserveScroll: true,
    onSuccess: () => { showImport.value = false },
  })
}

function statusLabel(status) {
  return { aktif: 'Aktif', luar: 'Luar', musafir: 'Musafir' }[status] ?? status
}

function statusVariant(status) {
  return { aktif: 'green', luar: 'gray', musafir: 'yellow' }[status] ?? 'gray'
}
</script>
