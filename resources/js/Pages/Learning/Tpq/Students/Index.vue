<template>
  <Head title="Data Santri" />

  <AdminLayout title="Data Santri">
    <PageHeader title="Data Santri" description="Kelola data santri TPQ.">
      <template #actions>
        <Link :href="route('admin.tpq.santri.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Tambah Santri</Link>
      </template>
    </PageHeader>

    <AppCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <AppInput v-model="filters.search" placeholder="Cari nama/NIS..." />
        <AppSelect v-model="filters.class_id" placeholder="Semua Kelas" :options="classes.map((c) => ({ label: c.name, value: c.id }))" />
        <AppSelect v-model="filters.status" placeholder="Semua Status" :options="[{ label: 'Aktif', value: 'aktif' }, { label: 'Cuti', value: 'cuti' }, { label: 'Lulus', value: 'lulus' }, { label: 'Keluar', value: 'keluar' }]" />
      </div>
    </AppCard>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Wali</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <tr v-if="students.data.length === 0">
              <td colspan="6" class="text-center text-[var(--text-muted)] py-8">Belum ada santri.</td>
            </tr>
            <tr v-for="student in students.data" :key="student.id">
              <td>{{ student.nis }}</td>
              <td>{{ student.name }}</td>
              <td>{{ student.student_classes?.[0]?.class?.name ?? '-' }}</td>
              <td>{{ student.guardian_name ?? '-' }} <span class="text-xs text-[var(--text-muted)]">({{ student.guardian_phone }})</span></td>
              <td><AppBadge :variant="statusVariant(student.status)">{{ statusLabel(student.status) }}</AppBadge></td>
              <td>
                <div class="flex items-center gap-2">
                  <Link :href="route('admin.tpq.santri.edit', student.id)" class="text-primary-600 text-sm hover:underline">Edit</Link>
                  <Link :href="route('admin.tpq.hafalan.show', student.id)" class="text-primary-600 text-sm hover:underline">Hafalan</Link>
                  <a :href="route('admin.tpq.santri.card', student.id)" target="_blank" class="text-primary-600 text-sm hover:underline">Kartu</a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="students.links" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Plus as PlusIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'

const props = defineProps({
  students: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  classes: { type: Array, default: () => [] },
  activeYear: { type: Object, default: null },
})

const filters = reactive({
  search: props.filters.search ?? '',
  class_id: props.filters.class_id ?? '',
  status: props.filters.status ?? '',
})

watch(filters, () => {
  router.get(route('admin.tpq.santri.index'), { ...filters }, { preserveState: true, replace: true })
}, { deep: true })

function statusLabel(status) {
  return { aktif: 'Aktif', cuti: 'Cuti', lulus: 'Lulus', keluar: 'Keluar' }[status] ?? status
}

function statusVariant(status) {
  return { aktif: 'green', cuti: 'yellow', lulus: 'blue', keluar: 'gray' }[status] ?? 'gray'
}
</script>
