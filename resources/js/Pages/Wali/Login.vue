<template>
  <Head title="Portal Wali Murid" />

  <div class="min-h-screen flex items-center justify-center p-4 bg-[var(--bg-base)]">
    <form class="w-full max-w-sm card p-6" @submit.prevent="submit">
      <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-xl bg-primary-600 flex items-center justify-center text-white text-xl mx-auto mb-3">🕌</div>
        <h1 class="text-lg font-bold text-[var(--text-primary)]">Portal Wali Murid</h1>
        <p class="text-sm text-[var(--text-muted)] mt-1">Pantau perkembangan ananda di TPQ.</p>
      </div>

      <div class="space-y-4">
        <AppInput v-model="form.phone" label="Nomor HP Wali" required :error="form.errors.phone" />
        <AppInput v-model="form.password" type="password" label="Kata Sandi (NIS santri)" required :error="form.errors.password" />
        <AppButton type="submit" class="w-full justify-center" :loading="form.processing">Masuk</AppButton>
      </div>

      <p class="text-xs text-[var(--text-muted)] text-center mt-4">
        Kata sandi default adalah Nomor Induk Santri (NIS). Hubungi pengurus TPQ jika lupa.
      </p>
    </form>
  </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const form = useForm({ phone: '', password: '' })

function submit() {
  form.post(route('wali.login'))
}
</script>
