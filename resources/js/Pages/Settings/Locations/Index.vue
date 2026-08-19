<template>
  <Head title="Lokasi Presensi" />

  <AdminLayout title="Lokasi Presensi">
    <PageHeader title="Lokasi Presensi Staf" description="Titik GPS yang jadi acuan presensi masuk/keluar ustadz lewat aplikasi Android.">
      <template #actions>
        <AppButton @click="openCreate"><PlusIcon class="w-4 h-4" /> Tambah Lokasi</AppButton>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-if="locations.length === 0" class="col-span-full">
        <EmptyState title="Belum ada lokasi presensi" description="Tambahkan minimal satu titik supaya ustadz bisa presensi lewat app." />
      </div>
      <div v-for="location in locations" :key="location.id" class="card p-4">
        <div class="flex items-start justify-between">
          <p class="font-medium text-[var(--text-primary)]">{{ location.name }}</p>
          <AppBadge :variant="location.is_active ? 'green' : 'gray'">{{ location.is_active ? 'Aktif' : 'Nonaktif' }}</AppBadge>
        </div>
        <p class="text-xs text-[var(--text-muted)] mt-2">{{ location.lat }}, {{ location.lng }}</p>
        <p class="text-xs text-[var(--text-muted)] mt-1">Radius {{ location.radius_meters }} m</p>
        <div class="flex gap-3 mt-3">
          <button class="text-primary-600 text-sm hover:underline" @click="openEdit(location)">Edit</button>
          <button class="text-red-500 text-sm hover:underline" @click="confirmDelete(location)">Hapus</button>
        </div>
      </div>
    </div>

    <AppModal :show="showModal" :title="editing ? 'Edit Lokasi' : 'Tambah Lokasi'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Lokasi" placeholder="Masjid Utama / Kelas Cabang..." required :error="form.errors.name" />

        <AppButton type="button" variant="secondary" class="w-full justify-center" :loading="locating" @click="useCurrentLocation">
          <LocateFixedIcon class="w-4 h-4" /> Gunakan Lokasi Saat Ini
        </AppButton>
        <p v-if="locateError" class="text-xs text-red-500 -mt-2">{{ locateError }}</p>

        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model.number="form.lat" type="number" step="0.00000001" label="Latitude" required :error="form.errors.lat" />
          <AppInput v-model.number="form.lng" type="number" step="0.00000001" label="Longitude" required :error="form.errors.lng" />
        </div>
        <AppInput v-model.number="form.radius_meters" type="number" label="Radius (meter)" hint="Jarak maksimal dari titik ini agar presensi diterima." required :error="form.errors.radius_meters" />
        <p class="text-xs text-[var(--text-muted)]">
          Tip: pastikan Anda sedang berada tepat di lokasi masjid/kelas saat klik "Gunakan Lokasi Saat Ini" di atas.
          Kalau tidak, isi manual lewat
          <a href="https://www.google.com/maps" target="_blank" rel="noopener" class="text-primary-600 hover:underline">Google Maps</a>
          (klik kanan titik lokasi, salin koordinatnya).
        </p>
        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input v-model="form.is_active" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
          Aktif
        </label>
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showModal = false">Batal</AppButton>
        <AppButton :loading="form.processing" @click="submit">Simpan</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Plus as PlusIcon, LocateFixed as LocateFixedIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

defineProps({
  locations: { type: Array, default: () => [] },
})

const showModal = ref(false)
const editing = ref(null)
const locating = ref(false)
const locateError = ref('')
const form = useForm({ name: '', lat: null, lng: null, radius_meters: 100, is_active: true })

function useCurrentLocation() {
  locateError.value = ''

  if (!navigator.geolocation) {
    locateError.value = 'Browser ini tidak mendukung deteksi lokasi.'
    return
  }

  locating.value = true
  navigator.geolocation.getCurrentPosition(
    (position) => {
      form.lat = Number(position.coords.latitude.toFixed(8))
      form.lng = Number(position.coords.longitude.toFixed(8))
      locating.value = false
    },
    (error) => {
      locateError.value = error.code === error.PERMISSION_DENIED
        ? 'Izin lokasi ditolak. Aktifkan izin lokasi untuk browser ini lalu coba lagi.'
        : 'Gagal mendapatkan lokasi. Coba lagi atau isi manual.'
      locating.value = false
    },
    { enableHighAccuracy: true, timeout: 15000 },
  )
}

function openCreate() {
  editing.value = null
  form.reset()
  form.radius_meters = 100
  form.is_active = true
  showModal.value = true
}

function openEdit(location) {
  editing.value = location
  form.name = location.name
  form.lat = Number(location.lat)
  form.lng = Number(location.lng)
  form.radius_meters = location.radius_meters
  form.is_active = location.is_active
  showModal.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.settings.lokasi-presensi.update', editing.value.id), options)
  } else {
    form.post(route('admin.settings.lokasi-presensi.store'), options)
  }
}

function confirmDelete(location) {
  if (!confirm(`Hapus lokasi "${location.name}"?`)) return
  router.delete(route('admin.settings.lokasi-presensi.destroy', location.id), { preserveScroll: true })
}
</script>
