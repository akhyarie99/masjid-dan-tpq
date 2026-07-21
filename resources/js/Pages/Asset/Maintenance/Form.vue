<template>
  <Head :title="isEdit ? 'Kelola Maintenance' : 'Jadwalkan Maintenance'" />

  <AdminLayout :title="isEdit ? 'Kelola Maintenance' : 'Jadwalkan Maintenance'">
    <PageHeader :title="isEdit ? 'Kelola Maintenance' : 'Jadwalkan Maintenance'" />

    <AppCard class="max-w-xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppSelect
          v-model="form.asset_id"
          label="Aset"
          required
          :disabled="isEdit"
          :options="assets.map((a) => ({ label: `${a.name} (${a.asset_code})`, value: a.id }))"
          :error="form.errors.asset_id"
        />
        <AppSelect v-model="form.type" label="Tipe" required :options="[{ label: 'Rutin', value: 'scheduled' }, { label: 'Perbaikan', value: 'repair' }, { label: 'Inspeksi', value: 'inspection' }]" :error="form.errors.type" />

        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Deskripsi</label>
          <textarea v-model="form.description" rows="3" class="input" required />
          <p v-if="form.errors.description" class="mt-1 text-xs text-red-500">{{ form.errors.description }}</p>
        </div>

        <AppInput v-model="form.scheduled_date" type="date" label="Tanggal Jadwal" required :error="form.errors.scheduled_date" />

        <template v-if="isEdit">
          <AppSelect v-model="form.status" label="Status" required :options="[{ label: 'Dijadwalkan', value: 'scheduled' }, { label: 'Diproses', value: 'in_progress' }, { label: 'Selesai', value: 'done' }]" :error="form.errors.status" />
          <div>
            <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Tindakan yang Dilakukan</label>
            <textarea v-model="form.action_taken" rows="2" class="input" />
          </div>
          <AppInput v-model.number="form.cost" type="number" label="Biaya (Rp)" :error="form.errors.cost" />
          <AppInput v-model="form.completed_date" type="date" label="Tanggal Selesai" :error="form.errors.completed_date" />
        </template>

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.asset.maintenance.index')" class="btn-secondary">Batal</Link>
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
  maintenance: { type: Object, default: null },
  assets: { type: Array, default: () => [] },
})

const isEdit = computed(() => props.maintenance !== null)

const form = useForm({
  asset_id: props.maintenance?.asset_id ?? '',
  type: props.maintenance?.type ?? 'scheduled',
  description: props.maintenance?.description ?? '',
  scheduled_date: props.maintenance?.scheduled_date ?? '',
  status: props.maintenance?.status ?? 'scheduled',
  action_taken: props.maintenance?.action_taken ?? '',
  cost: props.maintenance?.cost ?? '',
  completed_date: props.maintenance?.completed_date ?? '',
})

function submit() {
  if (isEdit.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.asset.maintenance.update', props.maintenance.id))
  } else {
    form.post(route('admin.asset.maintenance.store'))
  }
}
</script>
