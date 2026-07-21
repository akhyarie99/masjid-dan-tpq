<template>
  <Head title="Kajian Rutin" />

  <AdminLayout title="Kajian Rutin">
    <PageHeader title="Kajian Rutin" description="Kelola jadwal kajian rutin masjid.">
      <template #actions>
        <AppButton @click="openCreate"><PlusIcon class="w-4 h-4" /> Tambah Sesi</AppButton>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <EmptyState v-if="sessions.length === 0" title="Belum ada sesi kajian" class="col-span-full" />
      <div v-for="session in sessions" :key="session.id" class="card p-4">
        <div class="flex items-start justify-between">
          <p class="font-medium text-[var(--text-primary)]">{{ session.name }}</p>
          <AppBadge :variant="session.is_active ? 'green' : 'gray'">{{ session.is_active ? 'Aktif' : 'Nonaktif' }}</AppBadge>
        </div>
        <p class="text-sm text-[var(--text-muted)] mt-1">Ust. {{ session.ustadz_name }}</p>
        <p class="text-xs text-[var(--text-muted)] mt-2">
          {{ dayLabel(session.day_of_week) }}{{ session.time ? ` · ${session.time.slice(0,5)}` : '' }} · {{ session.location }}
        </p>
        <button class="text-primary-600 text-sm hover:underline mt-3" @click="openEdit(session)">Edit</button>
      </div>
    </div>

    <AppModal :show="showModal" :title="editing ? 'Edit Sesi Kajian' : 'Tambah Sesi Kajian'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Kajian" required :error="form.errors.name" />
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.ustadz_name" label="Nama Ustadz" required :error="form.errors.ustadz_name" />
          <AppInput v-model="form.ustadz_phone" label="No. HP Ustadz" :error="form.errors.ustadz_phone" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <AppSelect v-model="form.day_of_week" label="Hari" :options="dayOptions" :error="form.errors.day_of_week" />
          <AppInput v-model="form.time" type="time" label="Jam" :error="form.errors.time" />
        </div>
        <AppInput v-model="form.location" label="Lokasi" required :error="form.errors.location" />
        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input v-model="form.is_active" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
          Aktif
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
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

defineProps({
  sessions: { type: Array, default: () => [] },
})

const dayOptions = [
  { label: 'Senin', value: 'senin' }, { label: 'Selasa', value: 'selasa' }, { label: 'Rabu', value: 'rabu' },
  { label: 'Kamis', value: 'kamis' }, { label: 'Jumat', value: 'jumat' }, { label: 'Sabtu', value: 'sabtu' }, { label: 'Ahad', value: 'ahad' },
]

const showModal = ref(false)
const editing = ref(null)
const form = useForm({ name: '', ustadz_name: '', ustadz_phone: '', day_of_week: '', time: '', location: '', is_active: true })

function openCreate() {
  editing.value = null
  form.reset()
  showModal.value = true
}

function openEdit(session) {
  editing.value = session
  form.name = session.name
  form.ustadz_name = session.ustadz_name
  form.ustadz_phone = session.ustadz_phone
  form.day_of_week = session.day_of_week
  form.time = session.time?.slice(0, 5) ?? ''
  form.location = session.location
  form.is_active = session.is_active
  showModal.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.study.sesi.update', editing.value.id), options)
  } else {
    form.post(route('admin.study.sesi.store'), options)
  }
}

function dayLabel(day) {
  return dayOptions.find((d) => d.value === day)?.label ?? '-'
}
</script>
