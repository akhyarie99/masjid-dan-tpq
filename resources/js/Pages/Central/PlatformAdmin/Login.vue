<template>
  <Head title="Login Superadmin" />

  <div class="min-h-screen flex items-center justify-center p-4 bg-[var(--bg-base)]">
    <div class="w-full max-w-sm card p-6">
      <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-xl bg-primary-600 flex items-center justify-center text-white text-xl mx-auto mb-3">🛠️</div>
        <h1 class="text-lg font-bold text-[var(--text-primary)]">Superadmin Platform</h1>
        <p class="text-sm text-[var(--text-muted)] mt-1">Kelola semua tenant SiMasjid.</p>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.email" type="email" label="Email" required :error="form.errors.email" autofocus />
        <AppInput v-model="form.password" type="password" label="Kata Sandi" required :error="form.errors.password" />
        <AppButton type="submit" class="w-full justify-center" :loading="form.processing">Masuk</AppButton>
      </form>
    </div>
  </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const form = useForm({
  email: '',
  password: '',
})

function submit() {
  form.post(route('platform-admin.login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>
