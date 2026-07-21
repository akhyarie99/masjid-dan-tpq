<template>
  <Head :title="isEdit ? 'Edit Jamaah' : 'Tambah Jamaah'" />

  <AdminLayout :title="isEdit ? 'Edit Jamaah' : 'Tambah Jamaah'">
    <PageHeader :title="isEdit ? 'Edit Jamaah' : 'Tambah Jamaah'" />

    <AppCard class="max-w-xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Lengkap" required :error="form.errors.name" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.nik" label="NIK" :error="form.errors.nik" />
          <AppInput v-model="form.birth_date" type="date" label="Tanggal Lahir" :error="form.errors.birth_date" />
        </div>
        <AppInput v-model="form.phone" label="No. HP" required :error="form.errors.phone" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Alamat</label>
          <textarea v-model="form.address" rows="2" class="input" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.rt" label="RT" :error="form.errors.rt" />
          <AppInput v-model="form.rw" label="RW" :error="form.errors.rw" />
        </div>
        <AppSelect v-model="form.status" label="Status" required :options="[{ label: 'Aktif', value: 'aktif' }, { label: 'Luar', value: 'luar' }, { label: 'Musafir', value: 'musafir' }]" :error="form.errors.status" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Tag (pisahkan dengan koma)</label>
          <input v-model="tagsInput" type="text" class="input" placeholder="dhuafa, hafidz, lansia" />
        </div>
        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input v-model="form.receive_notification" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
          Bersedia menerima notifikasi WhatsApp
        </label>

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.jamaah.index')" class="btn-secondary">Batal</Link>
          <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
        </div>
      </form>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  jamaahProfile: { type: Object, default: null },
})

const isEdit = computed(() => props.jamaahProfile !== null)

const form = useForm({
  name: props.jamaahProfile?.name ?? '',
  nik: props.jamaahProfile?.nik ?? '',
  birth_date: props.jamaahProfile?.birth_date ?? '',
  phone: props.jamaahProfile?.phone ?? '',
  address: props.jamaahProfile?.address ?? '',
  rt: props.jamaahProfile?.rt ?? '',
  rw: props.jamaahProfile?.rw ?? '',
  status: props.jamaahProfile?.status ?? 'aktif',
  tags: props.jamaahProfile?.tags ?? [],
  receive_notification: props.jamaahProfile?.receive_notification ?? true,
})

const tagsInput = ref((props.jamaahProfile?.tags ?? []).join(', '))

watch(tagsInput, (value) => {
  form.tags = value.split(',').map((t) => t.trim()).filter(Boolean)
})

function submit() {
  if (isEdit.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.jamaah.update', props.jamaahProfile.id))
  } else {
    form.post(route('admin.jamaah.store'))
  }
}
</script>
