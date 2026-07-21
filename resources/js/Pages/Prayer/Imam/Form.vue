<template>
  <Head :title="isEdit ? 'Edit Imam' : 'Tambah Imam'" />

  <AdminLayout :title="isEdit ? 'Edit Imam' : 'Tambah Imam'">
    <PageHeader :title="isEdit ? 'Edit Imam' : 'Tambah Imam'" />

    <AppCard class="max-w-xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Imam" required :error="form.errors.name" />
        <AppInput v-model="form.phone" label="No. HP (untuk notifikasi WA)" :error="form.errors.phone" />
        <AppSelect v-model="form.type" label="Tipe" required :options="[{ label: 'Tetap', value: 'tetap' }, { label: 'Cadangan', value: 'cadangan' }, { label: 'Tamu', value: 'tamu' }]" :error="form.errors.type" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Bio</label>
          <textarea v-model="form.bio" rows="3" class="input" />
        </div>
        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input v-model="form.is_active" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
          Aktif
        </label>

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.prayer.imam.index')" class="btn-secondary">Batal</Link>
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
  imam: { type: Object, default: null },
})

const isEdit = computed(() => props.imam !== null)

const form = useForm({
  name: props.imam?.name ?? '',
  phone: props.imam?.phone ?? '',
  type: props.imam?.type ?? 'tetap',
  bio: props.imam?.bio ?? '',
  is_active: props.imam?.is_active ?? true,
})

function submit() {
  if (isEdit.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.prayer.imam.update', props.imam.id))
  } else {
    form.post(route('admin.prayer.imam.store'))
  }
}
</script>
