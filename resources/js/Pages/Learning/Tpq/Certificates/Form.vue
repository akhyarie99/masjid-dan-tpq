<template>
  <Head title="Terbitkan Sertifikat" />

  <AdminLayout title="Terbitkan Sertifikat">
    <PageHeader title="Terbitkan Sertifikat" />

    <AppCard class="max-w-xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppSelect v-model="form.student_id" label="Santri" required :options="students.map((s) => ({ label: `${s.name} (${s.nis})`, value: s.id }))" :error="form.errors.student_id" />
        <AppSelect v-model="form.type" label="Jenis Sertifikat" required :options="[{ label: 'Khatam Iqra', value: 'khatam_iqra' }, { label: 'Khatam Quran', value: 'khatam_quran' }, { label: 'Tahfidz', value: 'tahfidz' }, { label: 'Ijazah', value: 'ijazah' }]" :error="form.errors.type" />
        <AppInput v-model="form.achievement" label="Pencapaian (contoh: Khatam Iqra 6, Hafal Juz 30)" :error="form.errors.achievement" />
        <AppInput v-model="form.issued_date" type="date" label="Tanggal Terbit" required :error="form.errors.issued_date" />

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.tpq.sertifikat.index')" class="btn-secondary">Batal</Link>
          <AppButton type="submit" :loading="form.processing">Terbitkan</AppButton>
        </div>
      </form>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

defineProps({
  students: { type: Array, default: () => [] },
})

const form = useForm({
  student_id: '', type: 'khatam_iqra', achievement: '', issued_date: new Date().toISOString().slice(0, 10),
})

function submit() {
  form.post(route('admin.tpq.sertifikat.store'))
}
</script>
