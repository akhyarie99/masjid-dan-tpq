<template>
  <Head title="Masuk" />

  <div class="min-h-screen flex">
    <!-- Left illustration (desktop only) -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-700 to-primary-950 relative overflow-hidden items-center justify-center p-12">
      <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_20%_20%,white,transparent_35%)]" />
      <div class="relative text-center text-white max-w-md">
        <div class="w-20 h-20 rounded-2xl bg-white/10 backdrop-blur flex items-center justify-center mx-auto mb-6">
          <span class="font-arabic text-4xl">🕌</span>
        </div>
        <h1 class="text-3xl font-bold mb-3">SiMasjid</h1>
        <p class="text-primary-100">
          Sistem Informasi Manajemen Masjid — mengelola keuangan, aset, kegiatan, dan TPQ dalam satu platform.
        </p>
      </div>
    </div>

    <!-- Right form -->
    <div class="flex-1 flex flex-col">
      <div class="flex justify-between items-center p-4 md:p-6">
        <Link :href="route('home')" class="lg:hidden flex items-center gap-2">
          <div class="w-9 h-9 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold">
            🕌
          </div>
          <span class="font-semibold text-[var(--text-primary)]">SiMasjid</span>
        </Link>
        <div class="ml-auto">
          <ThemeToggle />
        </div>
      </div>

      <div class="flex-1 flex items-center justify-center p-4 md:p-6">
        <form class="w-full max-w-sm" @submit.prevent="submit">
          <h2 class="text-2xl font-bold text-[var(--text-primary)] mb-1">Masuk</h2>
          <p class="text-sm text-[var(--text-muted)] mb-6">Selamat datang kembali, silakan masuk ke akun Anda.</p>

          <AppAlert v-if="status" variant="success" class="mb-4">{{ status }}</AppAlert>

          <div class="space-y-4">
            <AppInput
              v-model="form.identifier"
              label="Nomor HP atau Email"
              placeholder="08xxxxxxxxxx atau nama@email.com"
              :error="form.errors.identifier"
              required
              autofocus
            />

            <AppInput
              v-model="form.password"
              type="password"
              label="Kata Sandi"
              placeholder="••••••••"
              :error="form.errors.password"
              required
            />

            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
                <input v-model="form.remember" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
                Ingat saya
              </label>
              <Link v-if="route().has('password.request')" :href="route('password.request')" class="text-sm text-primary-600 hover:underline">
                Lupa kata sandi?
              </Link>
            </div>

            <AppButton type="submit" class="w-full" :loading="form.processing">
              Masuk
            </AppButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ThemeToggle from '@/Components/Shared/ThemeToggle.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import AppAlert from '@/Components/UI/AppAlert.vue'

defineProps({
  status: { type: String, default: '' },
})

const form = useForm({
  identifier: '',
  password: '',
  remember: false,
})

function submit() {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>
