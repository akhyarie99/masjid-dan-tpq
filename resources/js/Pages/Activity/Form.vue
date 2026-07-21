<template>
  <Head :title="isEdit ? 'Edit Kegiatan' : 'Tambah Kegiatan'" />

  <AdminLayout :title="isEdit ? 'Edit Kegiatan' : 'Tambah Kegiatan'">
    <PageHeader :title="isEdit ? 'Edit Kegiatan' : 'Tambah Kegiatan'">
      <template #actions>
        <Link v-if="isEdit" :href="route('admin.activity.attendance', activity.id)" class="btn-secondary">Presensi</Link>
        <a v-if="isEdit" :href="route('admin.activity.qr', activity.id)" target="_blank" class="btn-secondary">QR Presensi</a>
      </template>
    </PageHeader>

    <AppCard class="max-w-2xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Kegiatan" required :error="form.errors.name" />

        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Deskripsi</label>
          <textarea v-model="form.description" rows="3" class="input" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppSelect v-model="form.category" label="Kategori" required :options="categoryOptions" :error="form.errors.category" />
          <AppInput v-model="form.location" label="Lokasi" required :error="form.errors.location" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.start_at" type="datetime-local" label="Waktu Mulai" required :error="form.errors.start_at" />
          <AppInput v-model="form.end_at" type="datetime-local" label="Waktu Selesai" :error="form.errors.end_at" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.pic_name" label="Nama PIC" :error="form.errors.pic_name" />
          <AppInput v-model="form.pic_phone" label="No. HP PIC" :error="form.errors.pic_phone" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model.number="form.quota" type="number" label="Kuota (kosongkan jika unlimited)" :error="form.errors.quota" />
          <AppSelect v-model="form.status" label="Status" required :options="statusOptions" :error="form.errors.status" />
        </div>

        <AppInput v-model="form.streaming_url" label="Link Streaming (opsional)" :error="form.errors.streaming_url" />

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.activity.calendar')" class="btn-secondary">Batal</Link>
          <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
        </div>
      </form>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  activity: { type: Object, default: null },
})

const isEdit = computed(() => props.activity !== null)

const categoryOptions = [
  { label: 'Kajian Rutin', value: 'kajian_rutin' },
  { label: 'Pengajian Akbar', value: 'pengajian_akbar' },
  { label: 'Sosial', value: 'sosial' },
  { label: 'PHBI', value: 'phbi' },
  { label: 'Rapat', value: 'rapat' },
  { label: 'Lainnya', value: 'lainnya' },
]

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Dipublikasikan', value: 'published' },
  { label: 'Berlangsung', value: 'ongoing' },
  { label: 'Selesai', value: 'done' },
  { label: 'Dibatalkan', value: 'cancelled' },
]

const form = useForm({
  name: props.activity?.name ?? '',
  description: props.activity?.description ?? '',
  category: props.activity?.category ?? 'kajian_rutin',
  location: props.activity?.location ?? '',
  start_at: props.activity?.start_at?.slice(0, 16) ?? '',
  end_at: props.activity?.end_at?.slice(0, 16) ?? '',
  pic_name: props.activity?.pic_name ?? '',
  pic_phone: props.activity?.pic_phone ?? '',
  quota: props.activity?.quota ?? '',
  status: props.activity?.status ?? 'draft',
  streaming_url: props.activity?.streaming_url ?? '',
})

function submit() {
  if (isEdit.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.activity.update', props.activity.id))
  } else {
    form.post(route('admin.activity.store'))
  }
}
</script>
