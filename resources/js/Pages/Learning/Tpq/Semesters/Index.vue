<template>
  <Head title="Semester" />

  <AdminLayout title="Semester">
    <PageHeader title="Semester" description="Kelola semester dalam tahun ajaran TPQ.">
      <template #actions>
        <AppButton @click="openCreate"><PlusIcon class="w-4 h-4" /> Tambah</AppButton>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="semesters" empty-text="Belum ada semester.">
        <template #cell-academic_year="{ row }">{{ row.academic_year?.name }}</template>
        <template #cell-is_active="{ value }"><AppBadge :variant="value ? 'green' : 'gray'">{{ value ? 'Aktif' : 'Nonaktif' }}</AppBadge></template>
        <template #cell-actions="{ row }">
          <button class="text-primary-600 text-sm hover:underline" @click="openEdit(row)">Edit</button>
        </template>
      </AppTable>
    </AppCard>

    <AppModal :show="showModal" :title="editing ? 'Edit Semester' : 'Tambah Semester'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="submit">
        <AppSelect v-model="form.academic_year_id" label="Tahun Ajaran" required :options="academicYears.map((y) => ({ label: y.name, value: y.id }))" :error="form.errors.academic_year_id" />
        <div class="grid grid-cols-2 gap-4">
          <AppSelect v-model="form.number" label="Semester Ke" required :options="[{ label: '1', value: 1 }, { label: '2', value: 2 }]" :error="form.errors.number" />
          <AppInput v-model="form.name" label="Nama" required :error="form.errors.name" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.start_date" type="date" label="Mulai" required :error="form.errors.start_date" />
          <AppInput v-model="form.end_date" type="date" label="Selesai" required :error="form.errors.end_date" />
        </div>
        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input v-model="form.is_active" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
          Jadikan semester aktif
        </label>
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showModal = false">Batal</AppButton>
        <AppButton :loading="form.processing" @click="submit">Simpan</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Plus as PlusIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppTable from '@/Components/UI/AppTable.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

defineProps({
  semesters: { type: Array, default: () => [] },
  academicYears: { type: Array, default: () => [] },
})

const columns = [
  { key: 'name', label: 'Semester' },
  { key: 'academic_year', label: 'Tahun Ajaran' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: '' },
]

const showModal = ref(false)
const editing = ref(null)
const form = useForm({ academic_year_id: '', number: 1, name: '', start_date: '', end_date: '', is_active: false })

function openCreate() {
  editing.value = null
  form.reset()
  showModal.value = true
}

function openEdit(semester) {
  editing.value = semester
  form.academic_year_id = semester.academic_year_id
  form.number = semester.number
  form.name = semester.name
  form.start_date = semester.start_date
  form.end_date = semester.end_date
  form.is_active = semester.is_active
  showModal.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.tpq.semester.update', editing.value.id), options)
  } else {
    form.post(route('admin.tpq.semester.store'), options)
  }
}
</script>
