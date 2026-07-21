<template>
  <Head :title="isEdit ? 'Edit Aset' : 'Tambah Aset'" />

  <AdminLayout :title="isEdit ? 'Edit Aset' : 'Tambah Aset'">
    <PageHeader :title="isEdit ? 'Edit Aset' : 'Tambah Aset'">
      <template #actions>
        <p v-if="isEdit" class="text-sm text-[var(--text-muted)]">Kode: <span class="font-mono">{{ asset.asset_code }}</span></p>
      </template>
    </PageHeader>

    <AppCard class="max-w-2xl">
      <form class="space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.name" label="Nama Aset" required :error="form.errors.name" />
          <AppSelect v-model="form.category_id" label="Kategori" required :options="categories.map((c) => ({ label: c.name, value: c.id }))" :error="form.errors.category_id" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <AppInput v-model="form.brand" label="Merek" :error="form.errors.brand" />
          <AppInput v-model="form.model" label="Model" :error="form.errors.model" />
          <AppInput v-model="form.serial_number" label="No. Seri" :error="form.errors.serial_number" />
        </div>

        <AppInput v-model="form.location" label="Lokasi" required :error="form.errors.location" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppSelect v-model="form.condition" label="Kondisi" required :options="[{ label: 'Baik', value: 'baik' }, { label: 'Cukup', value: 'cukup' }, { label: 'Rusak Ringan', value: 'rusak_ringan' }, { label: 'Rusak Berat', value: 'rusak_berat' }]" :error="form.errors.condition" />
          <AppSelect v-model="form.status" label="Status" required :options="[{ label: 'Aktif', value: 'aktif' }, { label: 'Dipinjam', value: 'dipinjam' }, { label: 'Perbaikan', value: 'perbaikan' }, { label: 'Dihapus', value: 'dihapus' }]" :error="form.errors.status" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model.number="form.purchase_price" type="number" label="Harga Beli (Rp)" :error="form.errors.purchase_price" />
          <AppInput v-model="form.purchase_date" type="date" label="Tanggal Beli" :error="form.errors.purchase_date" />
        </div>

        <AppInput v-model="form.vendor" label="Vendor/Toko" :error="form.errors.vendor" />

        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Deskripsi</label>
          <textarea v-model="form.description" rows="3" class="input" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model.number="form.maintenance_interval_days" type="number" label="Interval Perawatan (hari)" hint="Kosongkan jika tidak perlu perawatan rutin" :error="form.errors.maintenance_interval_days" />
          <AppInput v-model="form.next_maintenance_date" type="date" label="Perawatan Berikutnya" :error="form.errors.next_maintenance_date" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.asset.inventaris.index')" class="btn-secondary">Batal</Link>
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
  asset: { type: Object, default: null },
  categories: { type: Array, default: () => [] },
})

const isEdit = computed(() => props.asset !== null)

const form = useForm({
  category_id: props.asset?.category_id ?? '',
  name: props.asset?.name ?? '',
  brand: props.asset?.brand ?? '',
  model: props.asset?.model ?? '',
  serial_number: props.asset?.serial_number ?? '',
  location: props.asset?.location ?? '',
  condition: props.asset?.condition ?? 'baik',
  status: props.asset?.status ?? 'aktif',
  purchase_price: props.asset?.purchase_price ?? '',
  purchase_date: props.asset?.purchase_date ?? '',
  vendor: props.asset?.vendor ?? '',
  description: props.asset?.description ?? '',
  maintenance_interval_days: props.asset?.maintenance_interval_days ?? '',
  next_maintenance_date: props.asset?.next_maintenance_date ?? '',
})

function submit() {
  if (isEdit.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.asset.inventaris.update', props.asset.id))
  } else {
    form.post(route('admin.asset.inventaris.store'))
  }
}
</script>
