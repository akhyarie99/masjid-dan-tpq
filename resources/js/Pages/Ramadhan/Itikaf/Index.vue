<template>
  <Head title="I'tikaf" />

  <AdminLayout title="I'tikaf">
    <PageHeader title="Pendaftaran I'tikaf" description="Kelola pendaftaran itikaf 10 hari terakhir Ramadhan.">
      <template #actions>
        <AppButton @click="openCreate"><PlusIcon class="w-4 h-4" /> Tambah Pendaftar</AppButton>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Nama</th><th>No. HP</th><th>Periode</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <tr v-if="registrations.length === 0">
              <td colspan="5" class="text-center text-[var(--text-muted)] py-8">Belum ada pendaftar itikaf.</td>
            </tr>
            <tr v-for="registration in registrations" :key="registration.id">
              <td>{{ registration.name }}</td>
              <td>{{ registration.phone }}</td>
              <td>{{ formatDate(registration.start_date) }} — {{ formatDate(registration.end_date) }}</td>
              <td><AppBadge :variant="statusVariant(registration.status)">{{ statusLabel(registration.status) }}</AppBadge></td>
              <td><button class="text-primary-600 text-sm hover:underline" @click="openEdit(registration)">Edit</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <AppModal :show="showModal" :title="editing ? 'Edit Pendaftaran' : 'Tambah Pendaftar'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama" required :error="form.errors.name" />
        <AppInput v-model="form.phone" label="No. HP" required :error="form.errors.phone" />
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.start_date" type="date" label="Mulai" required :error="form.errors.start_date" />
          <AppInput v-model="form.end_date" type="date" label="Selesai" required :error="form.errors.end_date" />
        </div>
        <AppSelect v-model="form.status" label="Status" required :options="[{ label: 'Pending', value: 'pending' }, { label: 'Dikonfirmasi', value: 'confirmed' }, { label: 'Dibatalkan', value: 'cancelled' }]" :error="form.errors.status" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Catatan</label>
          <textarea v-model="form.notes" rows="2" class="input" />
        </div>
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
import dayjs from 'dayjs'
import { Plus as PlusIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

defineProps({
  registrations: { type: Array, default: () => [] },
})

const showModal = ref(false)
const editing = ref(null)
const form = useForm({ name: '', phone: '', start_date: '', end_date: '', status: 'pending', notes: '' })

function openCreate() {
  editing.value = null
  form.reset()
  showModal.value = true
}

function openEdit(registration) {
  editing.value = registration
  form.name = registration.name
  form.phone = registration.phone
  form.start_date = registration.start_date
  form.end_date = registration.end_date
  form.status = registration.status
  form.notes = registration.notes
  showModal.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.ramadhan.itikaf.update', editing.value.id), options)
  } else {
    form.post(route('admin.ramadhan.itikaf.store'), options)
  }
}

function statusLabel(status) {
  return { pending: 'Pending', confirmed: 'Dikonfirmasi', cancelled: 'Dibatalkan' }[status] ?? status
}

function statusVariant(status) {
  return { pending: 'yellow', confirmed: 'green', cancelled: 'gray' }[status] ?? 'gray'
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
