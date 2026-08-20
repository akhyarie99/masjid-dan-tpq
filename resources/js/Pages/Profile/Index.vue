<template>
  <Head title="Profil Saya" />

  <AdminLayout title="Profil Saya">
    <PageHeader title="Profil Saya" description="Kelola data pribadi dan foto profil Anda." />

    <AppCard title="Foto Profil" class="mb-6">
      <div class="flex items-center gap-4">
        <div class="w-20 h-20 rounded-full border border-[var(--border)] bg-[var(--bg-muted)] flex items-center justify-center overflow-hidden shrink-0">
          <img v-if="avatarPreview" :src="avatarPreview" alt="Foto profil" class="w-full h-full object-cover" />
          <span v-else class="text-2xl font-bold text-primary-600">{{ userInitial }}</span>
        </div>
        <div class="flex-1 min-w-0">
          <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="onAvatarSelected" />
          <AppButton type="button" variant="secondary" :loading="avatarForm.processing" @click="avatarInput.click()">
            Pilih Foto
          </AppButton>
          <p class="text-xs text-[var(--text-muted)] mt-2">Format PNG/JPG, maksimal 2MB.</p>
          <p v-if="avatarForm.errors.avatar" class="mt-1 text-xs text-red-500">{{ avatarForm.errors.avatar }}</p>
        </div>
      </div>
    </AppCard>

    <AppCard title="Data Pribadi" class="mb-6">
      <form class="space-y-4" @submit.prevent="submitProfile">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.name" label="Nama Lengkap" required :error="form.errors.name" />
          <AppSelect
            v-model="form.gender"
            label="Jenis Kelamin"
            :options="[{ label: 'Laki-laki', value: 'L' }, { label: 'Perempuan', value: 'P' }]"
            :error="form.errors.gender"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.phone" label="No. HP" required :error="form.errors.phone" />
          <AppInput v-model="form.email" type="email" label="Email" :error="form.errors.email" />
        </div>

        <AppInput v-model="form.birth_date" type="date" label="Tanggal Lahir" :error="form.errors.birth_date" />

        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Alamat</label>
          <textarea v-model="form.address" rows="3" class="input" />
          <p v-if="form.errors.address" class="mt-1 text-xs text-red-500">{{ form.errors.address }}</p>
        </div>

        <div class="flex justify-end">
          <AppButton type="submit" :loading="form.processing">Simpan Perubahan</AppButton>
        </div>
      </form>
    </AppCard>

    <AppCard title="Ubah Kata Sandi">
      <form class="space-y-4" @submit.prevent="submitPassword">
        <AppInput v-model="passwordForm.current_password" type="password" label="Kata Sandi Saat Ini" required :error="passwordForm.errors.current_password" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="passwordForm.password" type="password" label="Kata Sandi Baru" required :error="passwordForm.errors.password" />
          <AppInput v-model="passwordForm.password_confirmation" type="password" label="Ulangi Kata Sandi Baru" required />
        </div>
        <div class="flex justify-end">
          <AppButton type="submit" :loading="passwordForm.processing">Ubah Kata Sandi</AppButton>
        </div>
      </form>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  user: { type: Object, required: true },
})

const userInitial = computed(() => (props.user.name ?? '?').charAt(0).toUpperCase())

const form = useForm({
  name: props.user.name ?? '',
  email: props.user.email ?? '',
  phone: props.user.phone ?? '',
  birth_date: props.user.birth_date ?? '',
  address: props.user.address ?? '',
  gender: props.user.gender ?? '',
})

function submitProfile() {
  form.put(route('profile.update'))
}

const avatarInput = ref(null)
const avatarPreviewUrl = ref(null)
const avatarPreview = computed(() => avatarPreviewUrl.value ?? props.user.avatar_url)

const avatarForm = useForm({ avatar: null })

function onAvatarSelected(event) {
  const file = event.target.files?.[0]
  if (!file) return

  avatarPreviewUrl.value = URL.createObjectURL(file)
  avatarForm.avatar = file
  avatarForm.post(route('profile.avatar'), {
    preserveScroll: true,
    onSuccess: () => {
      avatarPreviewUrl.value = null
      avatarForm.reset()
    },
    onError: () => {
      avatarPreviewUrl.value = null
    },
    onFinish: () => {
      avatarInput.value.value = ''
    },
  })
}

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

function submitPassword() {
  passwordForm.put(route('profile.password'), {
    onSuccess: () => passwordForm.reset(),
  })
}
</script>
