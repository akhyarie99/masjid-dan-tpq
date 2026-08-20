<template>
  <Head title="Majelis Taklim" />

  <AdminLayout title="Majelis Taklim">
    <PageHeader title="Majelis Taklim" description="Kelola kelompok majelis taklim masjid.">
      <template v-if="can('study.manage')" #actions>
        <AppButton @click="openCreate"><PlusIcon class="w-4 h-4" /> Tambah Majelis</AppButton>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <EmptyState v-if="majelisList.length === 0" title="Belum ada majelis taklim" class="col-span-full" />
      <div v-for="majelis in majelisList" :key="majelis.id" class="card p-4">
        <div class="flex items-start justify-between">
          <p class="font-medium text-[var(--text-primary)]">{{ majelis.name }}</p>
          <AppBadge :variant="majelis.is_active ? 'green' : 'gray'">{{ majelis.is_active ? 'Aktif' : 'Nonaktif' }}</AppBadge>
        </div>
        <p class="text-sm text-[var(--text-muted)] mt-1">Ketua: {{ majelis.leader_name ?? '-' }}</p>
        <p class="text-xs text-[var(--text-muted)] mt-1">{{ majelis.members_count }} anggota</p>
        <div class="flex items-center gap-3 mt-3">
          <Link :href="route('admin.study.majelis.show', majelis.id)" class="text-primary-600 text-sm hover:underline">Kelola Anggota</Link>
          <button v-if="can('study.manage')" class="text-primary-600 text-sm hover:underline" @click="openEdit(majelis)">Edit</button>
        </div>
      </div>
    </div>

    <AppModal :show="showModal" :title="editing ? 'Edit Majelis' : 'Tambah Majelis'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Majelis" required :error="form.errors.name" />
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.leader_name" label="Nama Ketua" :error="form.errors.leader_name" />
          <AppInput v-model="form.leader_phone" label="No. HP Ketua" :error="form.errors.leader_phone" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.meeting_schedule" label="Jadwal Pertemuan" placeholder="cth: Kamis, 09:00" :error="form.errors.meeting_schedule" />
          <AppInput v-model="form.location" label="Lokasi" :error="form.errors.location" />
        </div>
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Deskripsi</label>
          <textarea v-model="form.description" rows="2" class="input" />
        </div>
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
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Plus as PlusIcon } from 'lucide-vue-next'
import { usePermission } from '@/composables/usePermission'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

defineProps({
  majelisList: { type: Array, default: () => [] },
})

const { can } = usePermission()

const showModal = ref(false)
const editing = ref(null)
const form = useForm({ name: '', description: '', leader_name: '', leader_phone: '', meeting_schedule: '', location: '', is_active: true })

function openCreate() {
  editing.value = null
  form.reset()
  showModal.value = true
}

function openEdit(majelis) {
  editing.value = majelis
  form.name = majelis.name
  form.description = majelis.description
  form.leader_name = majelis.leader_name
  form.leader_phone = majelis.leader_phone
  form.meeting_schedule = majelis.meeting_schedule
  form.location = majelis.location
  form.is_active = majelis.is_active
  showModal.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.study.majelis.update', editing.value.id), options)
  } else {
    form.post(route('admin.study.majelis.store'), options)
  }
}
</script>
