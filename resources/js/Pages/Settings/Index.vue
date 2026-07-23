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

    <AppCard title="Logo Masjid" subtitle="Logo ini dipakai di sidebar admin, portal publik, dan aplikasi Android." class="mb-6">
      <div class="flex items-center gap-4">
        <div class="w-20 h-20 rounded-xl border border-[var(--border)] bg-[var(--bg-muted)] flex items-center justify-center overflow-hidden shrink-0">
          <img v-if="logoPreview" :src="logoPreview" alt="Logo masjid" class="w-full h-full object-contain" />
          <span v-else class="text-2xl font-bold text-primary-600">{{ masjidInitial }}</span>
        </div>
        <div class="flex-1 min-w-0">
          <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="onLogoSelected" />
          <div class="flex flex-wrap gap-2">
            <AppButton type="button" variant="secondary" :loading="logoForm.processing" @click="logoInput.click()">
              Pilih Gambar
            </AppButton>
            <AppButton
              v-if="masjid.logo_url"
              type="button"
              variant="secondary"
              :loading="removeForm.processing"
              @click="confirmRemoveLogo"
            >
              Hapus Logo
            </AppButton>
          </div>
          <p class="text-xs text-[var(--text-muted)] mt-2">Format PNG/JPG/SVG, maksimal 2MB. Disarankan gambar persegi.</p>
          <p v-if="logoForm.errors.logo" class="mt-1 text-xs text-red-500">{{ logoForm.errors.logo }}</p>
        </div>
      </div>
    </AppCard>

    <AppCard title="Background Landing Page" subtitle="Gambar ini jadi latar belakang penuh layar di halaman utama (landing page) dan jam digital." class="mb-6">
      <div class="flex flex-col gap-4">
        <div class="w-full aspect-video max-w-md rounded-xl border border-[var(--border)] bg-[var(--bg-muted)] flex items-center justify-center overflow-hidden">
          <img v-if="backgroundPreview" :src="backgroundPreview" alt="Background landing page" class="w-full h-full object-cover" />
          <span v-else class="text-sm text-[var(--text-muted)]">Belum ada background</span>
        </div>
        <div>
          <input ref="backgroundInput" type="file" accept="image/*" class="hidden" @change="onBackgroundSelected" />
          <div class="flex flex-wrap gap-2">
            <AppButton type="button" variant="secondary" :loading="backgroundForm.processing" @click="backgroundInput.click()">
              Pilih Gambar
            </AppButton>
            <AppButton
              v-if="masjid.background_url"
              type="button"
              variant="secondary"
              :loading="removeBackgroundForm.processing"
              @click="confirmRemoveBackground"
            >
              Hapus Background
            </AppButton>
          </div>
          <p class="text-xs text-[var(--text-muted)] mt-2">Format PNG/JPG, maksimal 5MB. Disarankan gambar landscape beresolusi tinggi (mis. 1920x1080).</p>
          <p v-if="backgroundForm.errors.background_image" class="mt-1 text-xs text-red-500">{{ backgroundForm.errors.background_image }}</p>
        </div>
      </div>
    </AppCard>

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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput
            v-model="form.iqomah_offset_minutes"
            type="number"
            min="0"
            max="60"
            label="Jeda Iqomah (menit)"
            hint="Jarak waktu dari adzan ke iqomah, dipakai untuk pengingat di jam digital & landing page."
            :error="form.errors.iqomah_offset_minutes"
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
import { computed, ref } from 'vue'
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
  iqomah_offset_minutes: props.masjid.iqomah_offset_minutes,
})

function submit() {
  form.post(route('admin.settings.masjid'))
}

const masjidInitial = computed(() => (props.masjid.name ?? '?').charAt(0).toUpperCase())

const logoInput = ref(null)
const previewUrl = ref(null)
const logoPreview = computed(() => previewUrl.value ?? props.masjid.logo_url)

const logoForm = useForm({ logo: null })
const removeForm = useForm({})

function onLogoSelected(event) {
  const file = event.target.files?.[0]
  if (!file) return

  previewUrl.value = URL.createObjectURL(file)
  logoForm.logo = file
  logoForm.post(route('admin.settings.masjid.logo'), {
    preserveScroll: true,
    onSuccess: () => {
      previewUrl.value = null
      logoForm.reset()
    },
    onError: () => {
      previewUrl.value = null
    },
    onFinish: () => {
      logoInput.value.value = ''
    },
  })
}

function confirmRemoveLogo() {
  if (!confirm('Hapus logo masjid?')) return
  removeForm.delete(route('admin.settings.masjid.logo.destroy'), { preserveScroll: true })
}

const backgroundInput = ref(null)
const backgroundPreviewUrl = ref(null)
const backgroundPreview = computed(() => backgroundPreviewUrl.value ?? props.masjid.background_url)

const backgroundForm = useForm({ background_image: null })
const removeBackgroundForm = useForm({})

function onBackgroundSelected(event) {
  const file = event.target.files?.[0]
  if (!file) return

  backgroundPreviewUrl.value = URL.createObjectURL(file)
  backgroundForm.background_image = file
  backgroundForm.post(route('admin.settings.masjid.background'), {
    preserveScroll: true,
    onSuccess: () => {
      backgroundPreviewUrl.value = null
      backgroundForm.reset()
    },
    onError: () => {
      backgroundPreviewUrl.value = null
    },
    onFinish: () => {
      backgroundInput.value.value = ''
    },
  })
}

function confirmRemoveBackground() {
  if (!confirm('Hapus background landing page?')) return
  removeBackgroundForm.delete(route('admin.settings.masjid.background.destroy'), { preserveScroll: true })
}
</script>
