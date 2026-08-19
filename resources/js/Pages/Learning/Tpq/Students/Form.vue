<template>
  <Head :title="isEdit ? 'Edit Santri' : 'Tambah Santri'" />

  <AdminLayout :title="isEdit ? 'Edit Santri' : 'Tambah Santri'">
    <PageHeader :title="isEdit ? 'Edit Santri' : 'Tambah Santri'" />

    <AppCard class="max-w-2xl">
      <form class="space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.nis" label="NIS (kosongkan untuk otomatis)" :error="form.errors.nis" />
          <AppSelect v-model="form.class_id" label="Kelas" :options="classes.map((c) => ({ label: c.name, value: c.id }))" :error="form.errors.class_id" />
        </div>

        <AppInput v-model="form.name" label="Nama Lengkap" required :error="form.errors.name" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.nik" label="NIK" :error="form.errors.nik" />
          <AppSelect v-model="form.gender" label="Jenis Kelamin" required :options="[{ label: 'Laki-laki', value: 'L' }, { label: 'Perempuan', value: 'P' }]" :error="form.errors.gender" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.birth_place" label="Tempat Lahir" :error="form.errors.birth_place" />
          <AppInput v-model="form.birth_date" type="date" label="Tanggal Lahir" :error="form.errors.birth_date" />
        </div>

        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Alamat</label>
          <textarea v-model="form.address" rows="2" class="input" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.father_name" label="Nama Ayah" :error="form.errors.father_name" />
          <AppInput v-model="form.mother_name" label="Nama Ibu" :error="form.errors.mother_name" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.guardian_name" label="Nama Wali" :error="form.errors.guardian_name" />
          <AppInput v-model="form.parent_occupation" label="Pekerjaan Orang Tua" :error="form.errors.parent_occupation" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.guardian_phone" label="No. HP Wali (untuk login portal wali)" required :error="form.errors.guardian_phone" />
          <AppInput v-model="form.guardian_whatsapp" label="No. WhatsApp Wali" :error="form.errors.guardian_whatsapp" />
        </div>

        <AppInput
          v-model="form.guardian_email"
          type="email"
          label="Email Wali (opsional)"
          hint="Cadangan untuk lupa password kalau nomor HP di atas bukan nomor WhatsApp aktif."
          :error="form.errors.guardian_email"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppSelect v-model="form.status" label="Status" required :options="[{ label: 'Aktif', value: 'aktif' }, { label: 'Cuti', value: 'cuti' }, { label: 'Lulus', value: 'lulus' }, { label: 'Keluar', value: 'keluar' }]" :error="form.errors.status" />
          <AppInput v-model="form.entry_date" type="date" label="Tanggal Masuk" required :error="form.errors.entry_date" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.tpq.santri.index')" class="btn-secondary">Batal</Link>
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
  student: { type: Object, default: null },
  currentClassId: { type: String, default: null },
  classes: { type: Array, default: () => [] },
})

const isEdit = computed(() => props.student !== null)

const form = useForm({
  nis: props.student?.nis ?? '',
  class_id: props.currentClassId ?? '',
  name: props.student?.name ?? '',
  nik: props.student?.nik ?? '',
  gender: props.student?.gender ?? 'L',
  birth_place: props.student?.birth_place ?? '',
  birth_date: props.student?.birth_date ?? '',
  address: props.student?.address ?? '',
  father_name: props.student?.father_name ?? '',
  mother_name: props.student?.mother_name ?? '',
  guardian_name: props.student?.guardian_name ?? '',
  parent_occupation: props.student?.parent_occupation ?? '',
  guardian_phone: props.student?.guardian_phone ?? '',
  guardian_whatsapp: props.student?.guardian_whatsapp ?? '',
  guardian_email: props.student?.guardian_email ?? '',
  status: props.student?.status ?? 'aktif',
  entry_date: props.student?.entry_date ?? new Date().toISOString().slice(0, 10),
})

function submit() {
  if (isEdit.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.tpq.santri.update', props.student.id))
  } else {
    form.post(route('admin.tpq.santri.store'))
  }
}
</script>
