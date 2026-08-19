<template>
  <Head title="Buat Password Baru" />

  <div class="min-h-screen flex items-center justify-center p-4 bg-[var(--bg-base)]">
    <div class="w-full max-w-sm card p-6">
      <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-xl bg-primary-600 flex items-center justify-center text-white text-xl mx-auto mb-3">🔑</div>
        <h1 class="text-lg font-bold text-[var(--text-primary)]">Buat Password Baru</h1>
      </div>

      <div v-if="!valid" class="text-center">
        <p class="text-sm text-red-500">Link ini sudah kedaluwarsa atau tidak valid.</p>
        <Link :href="route('wali.forgot-password')" class="text-primary-600 text-sm hover:underline mt-3 inline-block">
          Minta link baru
        </Link>
      </div>

      <form v-else class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.password" type="password" label="Password Baru" required :error="form.errors.password" />
        <AppInput v-model="form.password_confirmation" type="password" label="Ulangi Password Baru" required />
        <AppButton type="submit" class="w-full justify-center" :loading="form.processing">Simpan Password</AppButton>
      </form>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  valid: { type: Boolean, required: true },
  token: { type: String, required: true },
  account: { type: String, default: null },
})

const form = useForm({
  account: props.account,
  token: props.token,
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post(route('wali.reset-password.store'))
}
</script>
