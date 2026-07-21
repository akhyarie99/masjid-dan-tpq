<template>
  <Head :title="isEdit ? 'Edit Proyek' : 'Buat Proyek'" />

  <AdminLayout :title="isEdit ? 'Edit Proyek' : 'Buat Proyek'">
    <PageHeader :title="isEdit ? 'Edit Proyek' : 'Buat Proyek Pembangunan'" />

    <AppCard class="max-w-xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Proyek" required :error="form.errors.name" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Deskripsi</label>
          <textarea v-model="form.description" rows="3" class="input" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model.number="form.target_amount" type="number" label="Target Dana (Rp)" required :error="form.errors.target_amount" />
          <AppInput v-model.number="form.collected_amount" type="number" label="Dana Terkumpul (Rp)" :error="form.errors.collected_amount" />
        </div>
        <AppInput v-model.number="form.physical_progress_percent" type="number" label="Progres Fisik (%)" :error="form.errors.physical_progress_percent" />
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.start_date" type="date" label="Mulai" required :error="form.errors.start_date" />
          <AppInput v-model="form.target_end_date" type="date" label="Target Selesai" :error="form.errors.target_end_date" />
        </div>
        <AppSelect v-model="form.status" label="Status" required :options="[{ label: 'Perencanaan', value: 'planning' }, { label: 'Berjalan', value: 'ongoing' }, { label: 'Selesai', value: 'completed' }]" :error="form.errors.status" />

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.wakaf.proyek.index')" class="btn-secondary">Batal</Link>
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
  project: { type: Object, default: null },
})

const isEdit = computed(() => props.project !== null)

const form = useForm({
  name: props.project?.name ?? '',
  description: props.project?.description ?? '',
  target_amount: props.project?.target_amount ?? '',
  collected_amount: props.project?.collected_amount ?? 0,
  physical_progress_percent: props.project?.physical_progress_percent ?? 0,
  start_date: props.project?.start_date ?? new Date().toISOString().slice(0, 10),
  target_end_date: props.project?.target_end_date ?? '',
  status: props.project?.status ?? 'planning',
})

function submit() {
  if (isEdit.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.wakaf.proyek.update', props.project.id))
  } else {
    form.post(route('admin.wakaf.proyek.store'))
  }
}
</script>
