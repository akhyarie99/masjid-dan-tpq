<template>
  <Head title="Tahun Ajaran" />

  <AdminLayout title="Tahun Ajaran">
    <TpqSubNav />
    <PageHeader title="Tahun Ajaran" description="Kelola tahun ajaran TPQ.">
      <template #actions>
        <AppButton @click="openCreate"><PlusIcon class="w-4 h-4" /> Tambah</AppButton>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="years" empty-text="Belum ada tahun ajaran.">
        <template #cell-is_active="{ value }"><AppBadge :variant="value ? 'green' : 'gray'">{{ value ? 'Aktif' : 'Nonaktif' }}</AppBadge></template>
        <template #cell-semesters_count="{ value }">{{ value }} semester</template>
        <template #cell-actions="{ row }">
          <button class="text-primary-600 text-sm hover:underline" @click="openEdit(row)">Edit</button>
        </template>
      </AppTable>
    </AppCard>

    <AppModal :show="showModal" :title="editing ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama (contoh: 2025/2026)" required :error="form.errors.name" />
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.start_date" type="date" label="Mulai" required :error="form.errors.start_date" />
          <AppInput v-model="form.end_date" type="date" label="Selesai" required :error="form.errors.end_date" />
        </div>
        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input v-model="form.is_active" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
          Jadikan tahun ajaran aktif
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
import TpqSubNav from '@/Components/Shared/TpqSubNav.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppTable from '@/Components/UI/AppTable.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

defineProps({
  years: { type: Array, default: () => [] },
})

const columns = [
  { key: 'name', label: 'Tahun Ajaran' },
  { key: 'semesters_count', label: 'Semester' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: '' },
]

const showModal = ref(false)
const editing = ref(null)
const form = useForm({ name: '', start_date: '', end_date: '', is_active: false })

function openCreate() {
  editing.value = null
  form.reset()
  showModal.value = true
}

function openEdit(year) {
  editing.value = year
  form.name = year.name
  form.start_date = year.start_date
  form.end_date = year.end_date
  form.is_active = year.is_active
  showModal.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.tpq.tahun-ajaran.update', editing.value.id), options)
  } else {
    form.post(route('admin.tpq.tahun-ajaran.store'), options)
  }
}
</script>
