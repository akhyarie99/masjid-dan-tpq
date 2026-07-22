<template>
  <Head title="Pengaturan TPQ" />

  <AdminLayout title="Pengaturan TPQ">
    <TpqSubNav />
    <PageHeader title="Pengaturan TPQ" description="Identitas lembaga dan kriteria kelulusan." />

    <AppCard class="max-w-2xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Lembaga TPQ" required :error="form.errors.name" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.sk_number" label="Nomor SK Pendirian" :error="form.errors.sk_number" />
          <AppInput v-model="form.head_nip" label="NIP Kepala TPQ" :error="form.errors.head_nip" />
        </div>
        <AppInput v-model="form.head_name" label="Nama Kepala TPQ" required :error="form.errors.head_name" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Alamat</label>
          <textarea v-model="form.address" rows="2" class="input" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model.number="form.min_attendance_percent" type="number" label="Min. Kehadiran (%)" required :error="form.errors.min_attendance_percent" />
          <AppInput v-model.number="form.min_avg_grade" type="number" label="Min. Rata-rata Nilai (KKM)" required :error="form.errors.min_avg_grade" />
        </div>

        <div class="flex justify-end pt-2">
          <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
        </div>
      </form>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import TpqSubNav from '@/Components/Shared/TpqSubNav.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  setting: { type: Object, required: true },
})

const form = useForm({
  name: props.setting.name,
  sk_number: props.setting.sk_number,
  head_name: props.setting.head_name,
  head_nip: props.setting.head_nip,
  address: props.setting.address,
  min_attendance_percent: props.setting.min_attendance_percent,
  min_avg_grade: props.setting.min_avg_grade,
})

function submit() {
  form.post(route('admin.tpq.pengaturan.update'))
}
</script>
