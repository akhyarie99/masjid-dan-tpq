<template>
  <Head title="Inventaris Aset" />

  <AdminLayout title="Inventaris Aset">
    <PageHeader title="Inventaris Aset" description="Kelola seluruh aset dan perlengkapan masjid.">
      <template #actions>
        <button class="btn-secondary" @click="view = view === 'grid' ? 'table' : 'grid'">
          <component :is="view === 'grid' ? ListIcon : GridIcon" class="w-4 h-4" />
          {{ view === 'grid' ? 'Tabel' : 'Grid' }}
        </button>
        <Link :href="route('admin.asset.inventaris.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Tambah Aset</Link>
      </template>
    </PageHeader>

    <AppCard class="mb-6">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <AppSelect v-model="filters.category_id" placeholder="Semua Kategori" :options="categories.map((c) => ({ label: c.name, value: c.id }))" />
        <AppSelect v-model="filters.condition" placeholder="Semua Kondisi" :options="conditionOptions" />
        <AppSelect v-model="filters.status" placeholder="Semua Status" :options="statusOptions" />
        <AppInput v-model="filters.search" placeholder="Cari nama/kode..." />
      </div>
    </AppCard>

    <!-- Grid -->
    <div v-if="view === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <div v-if="assets.data.length === 0" class="col-span-full">
        <EmptyState title="Belum ada aset" description="Tambahkan aset pertama masjid Anda." />
      </div>
      <div v-for="asset in assets.data" :key="asset.id" class="card p-4">
        <div class="h-28 rounded-lg bg-[var(--bg-muted)] flex items-center justify-center mb-3 text-3xl">
          📦
        </div>
        <p class="font-medium text-[var(--text-primary)] truncate">{{ asset.name }}</p>
        <p class="text-xs text-[var(--text-muted)]">{{ asset.asset_code }}</p>
        <div class="flex items-center gap-1.5 mt-2">
          <AppBadge :variant="conditionVariant(asset.condition)">{{ conditionLabel(asset.condition) }}</AppBadge>
          <AppBadge :variant="statusVariant(asset.status)">{{ statusLabel(asset.status) }}</AppBadge>
        </div>
        <p class="text-xs text-[var(--text-muted)] mt-2 truncate">📍 {{ asset.location }}</p>
        <div class="flex items-center gap-2 mt-3">
          <Link :href="route('admin.asset.inventaris.edit', asset.id)" class="text-primary-600 text-sm hover:underline">Detail</Link>
          <a :href="route('admin.asset.inventaris.qr', asset.id)" target="_blank" class="text-primary-600 text-sm hover:underline">QR Code</a>
        </div>
      </div>
    </div>

    <!-- Table -->
    <AppCard v-else :padded="false">
      <AppTable :columns="columns" :rows="assets.data" empty-text="Belum ada aset.">
        <template #cell-condition="{ value }"><AppBadge :variant="conditionVariant(value)">{{ conditionLabel(value) }}</AppBadge></template>
        <template #cell-status="{ value }"><AppBadge :variant="statusVariant(value)">{{ statusLabel(value) }}</AppBadge></template>
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-2">
            <Link :href="route('admin.asset.inventaris.edit', row.id)" class="text-primary-600 text-sm hover:underline">Edit</Link>
            <a :href="route('admin.asset.inventaris.qr', row.id)" target="_blank" class="text-primary-600 text-sm hover:underline">QR</a>
          </div>
        </template>
      </AppTable>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="assets.links" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Plus as PlusIcon, LayoutGrid as GridIcon, List as ListIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppTable from '@/Components/UI/AppTable.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

const props = defineProps({
  assets: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
})

const view = ref('grid')

const filters = reactive({
  category_id: props.filters.category_id ?? '',
  condition: props.filters.condition ?? '',
  status: props.filters.status ?? '',
  search: props.filters.search ?? '',
})

watch(filters, () => {
  router.get(route('admin.asset.inventaris.index'), { ...filters }, { preserveState: true, replace: true })
}, { deep: true })

const columns = [
  { key: 'name', label: 'Nama' },
  { key: 'asset_code', label: 'Kode' },
  { key: 'location', label: 'Lokasi' },
  { key: 'condition', label: 'Kondisi' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '' },
]

const conditionOptions = [
  { label: 'Baik', value: 'baik' }, { label: 'Cukup', value: 'cukup' },
  { label: 'Rusak Ringan', value: 'rusak_ringan' }, { label: 'Rusak Berat', value: 'rusak_berat' },
]

const statusOptions = [
  { label: 'Aktif', value: 'aktif' }, { label: 'Dipinjam', value: 'dipinjam' },
  { label: 'Perbaikan', value: 'perbaikan' }, { label: 'Dihapus', value: 'dihapus' },
]

function conditionLabel(value) {
  return { baik: 'Baik', cukup: 'Cukup', rusak_ringan: 'Rusak Ringan', rusak_berat: 'Rusak Berat' }[value] ?? value
}

function conditionVariant(value) {
  return { baik: 'green', cukup: 'blue', rusak_ringan: 'yellow', rusak_berat: 'red' }[value] ?? 'gray'
}

function statusLabel(value) {
  return { aktif: 'Aktif', dipinjam: 'Dipinjam', perbaikan: 'Perbaikan', dihapus: 'Dihapus' }[value] ?? value
}

function statusVariant(value) {
  return { aktif: 'green', dipinjam: 'blue', perbaikan: 'yellow', dihapus: 'gray' }[value] ?? 'gray'
}
</script>
