<template>
  <Head title="Pengaturan" />

  <AdminLayout title="Pengaturan">
    <PageHeader title="Pengaturan" description="Kelola profil masjid dan data pengguna.">
      <template #actions>
        <Link v-if="route().has('admin.settings.pengguna.index')" :href="route('admin.settings.pengguna.index')" class="btn-secondary">
          <UsersIcon class="w-4 h-4" /> Manajemen Pengguna
        </Link>
        <Link v-if="route().has('admin.settings.audit-log')" :href="route('admin.settings.audit-log')" class="btn-secondary">
          <HistoryIcon class="w-4 h-4" /> Log Aktivitas
        </Link>
      </template>
    </PageHeader>

    <AppCard title="Profil Masjid" subtitle="Informasi ini ditampilkan di portal publik dan digunakan untuk kalkulasi jadwal shalat.">
      <form class="space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.name" label="Nama Masjid" required :error="form.errors.name" />
          <AppSelect
            v-model="form.prayer_method"
            label="Metode Perhitungan Shalat"
            :options="[{ label: 'Kemenag RI', value: 'kemenag' }, { label: 'Muslim World League', value: 'mwl' }, { label: 'ISNA', value: 'isna' }]"
            :error="form.errors.prayer_method"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Alamat</label>
          <textarea v-model="form.address" rows="3" class="input" required />
          <p v-if="form.errors.address" class="mt-1 text-xs text-red-500">{{ form.errors.address }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.latitude" type="number" label="Latitude" hint="Koordinat untuk kalkulasi jadwal shalat" :error="form.errors.latitude" />
          <AppInput v-model="form.longitude" type="number" label="Longitude" :error="form.errors.longitude" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.phone" label="Telepon/WhatsApp" :error="form.errors.phone" />
          <AppInput v-model="form.email" type="email" label="Email" :error="form.errors.email" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.instagram" label="Link Instagram" :error="form.errors.instagram" />
          <AppInput v-model="form.youtube" label="Link YouTube" :error="form.errors.youtube" />
        </div>

        <div class="flex justify-end">
          <AppButton type="submit" :loading="form.processing">Simpan Perubahan</AppButton>
        </div>
      </form>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Users as UsersIcon, History as HistoryIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  masjid: { type: Object, required: true },
})

const form = useForm({
  name: props.masjid.name,
  address: props.masjid.address,
  latitude: props.masjid.latitude,
  longitude: props.masjid.longitude,
  phone: props.masjid.phone,
  email: props.masjid.email,
  website: props.masjid.website,
  instagram: props.masjid.instagram,
  youtube: props.masjid.youtube,
  vision: props.masjid.vision,
  mission: props.masjid.mission,
  prayer_method: props.masjid.prayer_method,
})

function submit() {
  form.post(route('admin.settings.masjid'))
}
</script>
