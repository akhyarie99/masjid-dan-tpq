<template>
  <Head title="Ajukan Peminjaman" />

  <AdminLayout title="Ajukan Peminjaman">
    <PageHeader title="Ajukan Peminjaman Aset" />

    <AppCard class="max-w-xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppSelect v-model="form.asset_id" label="Aset" required :options="assets.map((a) => ({ label: `${a.name} (${a.asset_code})`, value: a.id }))" :error="form.errors.asset_id" />
        <AppInput v-model="form.borrower_name" label="Nama Peminjam" required :error="form.errors.borrower_name" />
        <AppInput v-model="form.borrower_phone" label="No. HP Peminjam" required :error="form.errors.borrower_phone" />
        <AppInput v-model="form.purpose" label="Keperluan" required :error="form.errors.purpose" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.loan_date" type="date" label="Tanggal Pinjam" required :error="form.errors.loan_date" />
          <AppInput v-model="form.return_date_planned" type="date" label="Rencana Kembali" required :error="form.errors.return_date_planned" />
        </div>
        <AppSelect v-model="form.condition_out" label="Kondisi Saat Dipinjam" required :options="[{ label: 'Baik', value: 'baik' }, { label: 'Cukup', value: 'cukup' }, { label: 'Rusak Ringan', value: 'rusak_ringan' }]" :error="form.errors.condition_out" />

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.asset.peminjaman.index')" class="btn-secondary">Batal</Link>
          <AppButton type="submit" :loading="form.processing">Ajukan</AppButton>
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
  assets: { type: Array, default: () => [] },
})

const form = useForm({
  asset_id: '', borrower_name: '', borrower_phone: '', purpose: '',
  loan_date: new Date().toISOString().slice(0, 10), return_date_planned: '', condition_out: 'baik',
})

function submit() {
  form.post(route('admin.asset.peminjaman.store'))
}
</script>
