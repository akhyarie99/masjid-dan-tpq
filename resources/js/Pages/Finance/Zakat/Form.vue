<template>
  <Head :title="isEdit ? 'Edit Zakat' : 'Catat Zakat'" />

  <AdminLayout :title="isEdit ? 'Edit Zakat' : 'Catat Zakat'">
    <PageHeader :title="isEdit ? 'Edit Zakat' : 'Catat Zakat'" />

    <AppCard class="max-w-xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppSelect v-model="form.type" label="Jenis Zakat" required :options="[{ label: 'Fitrah', value: 'fitrah' }, { label: 'Maal', value: 'maal' }, { label: 'Profesi', value: 'profesi' }, { label: 'Infaq', value: 'infaq' }]" :error="form.errors.type" />
        <AppInput v-model="form.payer_name" label="Nama Muzakki" required :error="form.errors.payer_name" />
        <AppInput v-model="form.payer_phone" label="No. HP" :error="form.errors.payer_phone" />
        <AppInput v-model.number="form.dependents" type="number" label="Jumlah Jiwa" required :error="form.errors.dependents" />

        <AppSelect v-model="form.payment_type" label="Tipe Pembayaran" required :options="[{ label: 'Uang', value: 'uang' }, { label: 'Beras', value: 'beras' }]" :error="form.errors.payment_type" />

        <template v-if="form.payment_type === 'uang'">
          <AppInput v-model.number="form.amount_per_person" type="number" label="Nominal per Jiwa (Rp)" @input="recalculate" />
          <AppInput v-model.number="form.total_amount" type="number" label="Total Nominal (Rp)" required :error="form.errors.total_amount" />
        </template>
        <template v-else>
          <AppInput v-model.number="form.rice_kg" type="number" label="Total Beras (kg)" required :error="form.errors.rice_kg" />
        </template>

        <AppInput v-model.number="form.year" type="number" label="Tahun" required :error="form.errors.year" />
        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input v-model="form.ramadhan" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
          Terkait Ramadhan
        </label>

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.finance.zakat.index')" class="btn-secondary">Batal</Link>
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
  record: { type: Object, default: null },
})

const isEdit = computed(() => props.record !== null)

const form = useForm({
  type: props.record?.type ?? 'fitrah',
  payer_name: props.record?.payer_name ?? '',
  payer_phone: props.record?.payer_phone ?? '',
  dependents: props.record?.dependents ?? 1,
  payment_type: props.record?.payment_type ?? 'uang',
  amount_per_person: props.record?.amount_per_person ?? '',
  total_amount: props.record?.total_amount ?? 0,
  rice_kg: props.record?.rice_kg ?? '',
  year: props.record?.year ?? new Date().getFullYear(),
  ramadhan: props.record?.ramadhan ?? true,
})

function recalculate() {
  if (form.amount_per_person) {
    form.total_amount = Number(form.amount_per_person) * Number(form.dependents || 1)
  }
}

function submit() {
  if (isEdit.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.finance.zakat.penerimaan.update', props.record.id))
  } else {
    form.post(route('admin.finance.zakat.penerimaan.store'))
  }
}
</script>
