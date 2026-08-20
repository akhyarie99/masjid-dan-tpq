<template>
  <Head :title="majelis.name" />

  <AdminLayout :title="majelis.name">
    <PageHeader :title="majelis.name" :description="`${members.length} anggota`">
      <template #actions>
        <Link :href="route('admin.study.majelis.index')" class="btn-secondary">Kembali</Link>
        <AppButton v-if="can('study.manage')" @click="openCreate"><PlusIcon class="w-4 h-4" /> Tambah Anggota</AppButton>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="members" empty-text="Belum ada anggota.">
        <template #cell-joined_date="{ value }">{{ formatDate(value) }}</template>
        <template #cell-actions="{ row }">
          <div v-if="can('study.manage')" class="flex items-center gap-2">
            <button class="text-primary-600 text-sm hover:underline" @click="openEdit(row)">Edit</button>
            <button class="text-red-500 text-sm hover:underline" @click="destroy(row)">Hapus</button>
          </div>
        </template>
      </AppTable>
    </AppCard>

    <AppModal :show="showModal" :title="editing ? 'Edit Anggota' : 'Tambah Anggota'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama" required :error="form.errors.name" />
        <AppInput v-model="form.phone" label="No. HP" :error="form.errors.phone" />
        <AppInput v-model="form.joined_date" type="date" label="Tanggal Bergabung" required :error="form.errors.joined_date" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Alamat</label>
          <textarea v-model="form.address" rows="2" class="input" />
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
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Plus as PlusIcon } from 'lucide-vue-next'
import { usePermission } from '@/composables/usePermission'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppTable from '@/Components/UI/AppTable.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  majelis: { type: Object, required: true },
  members: { type: Array, default: () => [] },
})

const { can } = usePermission()

const columns = [
  { key: 'name', label: 'Nama' },
  { key: 'phone', label: 'No. HP' },
  { key: 'joined_date', label: 'Bergabung' },
  { key: 'actions', label: '' },
]

const showModal = ref(false)
const editing = ref(null)
const form = useForm({ name: '', phone: '', address: '', joined_date: new Date().toISOString().slice(0, 10) })

function openCreate() {
  editing.value = null
  form.reset()
  form.joined_date = new Date().toISOString().slice(0, 10)
  showModal.value = true
}

function openEdit(member) {
  editing.value = member
  form.name = member.name
  form.phone = member.phone
  form.address = member.address
  form.joined_date = member.joined_date
  showModal.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.study.majelis.anggota.update', [props.majelis.id, editing.value.id]), options)
  } else {
    form.post(route('admin.study.majelis.anggota.store', props.majelis.id), options)
  }
}

function destroy(member) {
  if (confirm(`Hapus anggota ${member.name}?`)) {
    router.delete(route('admin.study.majelis.anggota.destroy', [props.majelis.id, member.id]), { preserveScroll: true })
  }
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
