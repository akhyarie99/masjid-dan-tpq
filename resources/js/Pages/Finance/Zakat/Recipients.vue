<template>
  <Head title="Penerima Zakat" />

  <AdminLayout title="Penerima Zakat">
    <PageHeader title="Data Penerima Zakat (Mustahik)" description="Kelola daftar 8 asnaf penerima zakat.">
      <template #actions>
        <Link :href="route('admin.finance.zakat.index')" class="btn-secondary">Kembali</Link>
        <AppButton @click="openCreate"><PlusIcon class="w-4 h-4" /> Tambah Penerima</AppButton>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="recipients" empty-text="Belum ada data penerima zakat.">
        <template #cell-category="{ value }"><AppBadge variant="blue">{{ categoryLabel(value) }}</AppBadge></template>
        <template #cell-is_active="{ value }"><AppBadge :variant="value ? 'green' : 'gray'">{{ value ? 'Aktif' : 'Nonaktif' }}</AppBadge></template>
        <template #cell-actions="{ row }">
          <button class="text-primary-600 text-sm hover:underline" @click="openEdit(row)">Edit</button>
        </template>
      </AppTable>
    </AppCard>

    <AppModal :show="showModal" :title="editing ? 'Edit Penerima' : 'Tambah Penerima'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama" required :error="form.errors.name" />
        <AppInput v-model="form.phone" label="No. HP" :error="form.errors.phone" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Alamat</label>
          <textarea v-model="form.address" rows="2" class="input" required />
        </div>
        <AppSelect v-model="form.category" label="Kategori (Asnaf)" required :options="categoryOptions" :error="form.errors.category" />
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
  recipients: { type: Array, default: () => [] },
})

const columns = [
  { key: 'name', label: 'Nama' },
  { key: 'category', label: 'Kategori' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: '' },
]

const categoryOptions = [
  { label: 'Fakir', value: 'fakir' }, { label: 'Miskin', value: 'miskin' }, { label: 'Amil', value: 'amil' },
  { label: 'Muallaf', value: 'muallaf' }, { label: 'Riqab', value: 'riqab' }, { label: 'Gharimin', value: 'gharimin' },
  { label: 'Fisabilillah', value: 'fisabilillah' }, { label: 'Ibnus Sabil', value: 'ibnus_sabil' },
]

const showModal = ref(false)
const editing = ref(null)
const form = useForm({ name: '', phone: '', address: '', category: 'fakir', is_active: true })

function openCreate() {
  editing.value = null
  form.reset()
  showModal.value = true
}

function openEdit(recipient) {
  editing.value = recipient
  form.name = recipient.name
  form.phone = recipient.phone
  form.address = recipient.address
  form.category = recipient.category
  form.is_active = recipient.is_active
  showModal.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.finance.zakat.penerima.update', editing.value.id), options)
  } else {
    form.post(route('admin.finance.zakat.penerima.store'), options)
  }
}

function categoryLabel(value) {
  return { fakir: 'Fakir', miskin: 'Miskin', amil: 'Amil', muallaf: 'Muallaf', riqab: 'Riqab', gharimin: 'Gharimin', fisabilillah: 'Fisabilillah', ibnus_sabil: 'Ibnus Sabil' }[value] ?? value
}
</script>
