<template>
  <Head :title="isEdit ? 'Edit Wakaf' : 'Catat Wakaf'" />

  <AdminLayout :title="isEdit ? 'Edit Wakaf' : 'Catat Wakaf'">
    <PageHeader :title="isEdit ? 'Edit Wakaf' : 'Catat Wakaf'" />

    <AppCard class="max-w-xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.wakif_name" label="Nama Wakif" required :error="form.errors.wakif_name" />
        <AppInput v-model="form.wakif_phone" label="No. HP" :error="form.errors.wakif_phone" />
        <AppSelect v-model="form.type" label="Jenis Wakaf" required :options="[{ label: 'Tanah', value: 'tanah' }, { label: 'Bangunan', value: 'bangunan' }, { label: 'Uang', value: 'uang' }, { label: 'Lainnya', value: 'lainnya' }]" :error="form.errors.type" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Deskripsi</label>
          <textarea v-model="form.description" rows="3" class="input" />
        </div>
        <AppInput v-model.number="form.estimated_value" type="number" label="Estimasi Nilai (Rp)" :error="form.errors.estimated_value" />
        <AppInput v-model="form.certificate_number" label="Nomor Sertifikat (opsional)" :error="form.errors.certificate_number" />
        <div class="grid grid-cols-2 gap-4">
          <AppSelect v-model="form.status" label="Status" required :options="[{ label: 'Proses', value: 'proses' }, { label: 'Selesai', value: 'selesai' }]" :error="form.errors.status" />
          <AppInput v-model="form.donated_date" type="date" label="Tanggal Wakaf" required :error="form.errors.donated_date" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.wakaf.index')" class="btn-secondary">Batal</Link>
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
  wakif_name: props.record?.wakif_name ?? '',
  wakif_phone: props.record?.wakif_phone ?? '',
  type: props.record?.type ?? 'tanah',
  description: props.record?.description ?? '',
  estimated_value: props.record?.estimated_value ?? '',
  certificate_number: props.record?.certificate_number ?? '',
  status: props.record?.status ?? 'proses',
  donated_date: props.record?.donated_date ?? new Date().toISOString().slice(0, 10),
})

function submit() {
  if (isEdit.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.wakaf.update', props.record.id))
  } else {
    form.post(route('admin.wakaf.store'))
  }
}
</script>
