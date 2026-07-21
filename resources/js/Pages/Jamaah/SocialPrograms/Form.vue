<template>
  <Head title="Buat Program Sosial" />

  <AdminLayout title="Buat Program Sosial">
    <PageHeader title="Buat Program Sosial" />

    <AppCard class="max-w-xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Program" required :error="form.errors.name" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Deskripsi</label>
          <textarea v-model="form.description" rows="3" class="input" />
        </div>
        <AppInput v-model.number="form.budget" type="number" label="Anggaran (Rp)" required :error="form.errors.budget" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.start_date" type="date" label="Mulai" required :error="form.errors.start_date" />
          <AppInput v-model="form.end_date" type="date" label="Selesai" :error="form.errors.end_date" />
        </div>
        <AppSelect v-model="form.status" label="Status" required :options="[{ label: 'Draft', value: 'draft' }, { label: 'Aktif', value: 'active' }, { label: 'Selesai', value: 'closed' }]" :error="form.errors.status" />

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.jamaah.program-sosial.index')" class="btn-secondary">Batal</Link>
          <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
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

const form = useForm({ name: '', description: '', budget: 0, start_date: new Date().toISOString().slice(0, 10), end_date: '', status: 'draft' })

function submit() {
  form.post(route('admin.jamaah.program-sosial.store'))
}
</script>
