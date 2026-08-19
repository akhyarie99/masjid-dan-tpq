<template>
  <Head title="Daftarkan Lembaga" />

  <div class="min-h-screen flex items-center justify-center p-4 bg-[var(--bg-base)]">
    <div class="w-full max-w-lg card p-6 md:p-8">
      <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-xl bg-primary-600 flex items-center justify-center text-white text-xl mx-auto mb-3">🕌</div>
        <h1 class="text-lg font-bold text-[var(--text-primary)]">Daftarkan Lembaga</h1>
        <p class="text-sm text-[var(--text-muted)] mt-1">Portal masjid/TPQ Anda siap dipakai setelah ini, gratis dicoba.</p>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Lembaga" placeholder="Masjid / TPQ ..." required :error="form.errors.name" />
        <AppInput v-model="form.address" label="Alamat" required :error="form.errors.address" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.admin_name" label="Nama Admin" required :error="form.errors.admin_name" />
          <AppInput v-model="form.admin_phone" label="No. HP Admin" placeholder="08xxxxxxxxxx" required :error="form.errors.admin_phone" />
        </div>

        <AppInput v-model="form.admin_email" type="email" label="Email Admin (opsional)" :error="form.errors.admin_email" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.password" type="password" label="Kata Sandi" required :error="form.errors.password" />
          <AppInput v-model="form.password_confirmation" type="password" label="Ulangi Kata Sandi" required />
        </div>

        <!-- Honeypot: disembunyikan dari manusia lewat CSS, bot pengisi form otomatis
             biasanya tetap mengisi semua field yang ada di DOM. -->
        <div class="absolute -left-[9999px]" aria-hidden="true">
          <label for="website_hp">Situs Web</label>
          <input id="website_hp" v-model="form.website_hp" type="text" tabindex="-1" autocomplete="off" />
        </div>

        <AppButton type="submit" class="w-full justify-center" :loading="form.processing">Daftar</AppButton>
      </form>

      <p class="text-xs text-center mt-4 text-[var(--text-muted)]">
        Sudah punya portal? Login lewat alamat portal lembaga Anda masing-masing.
      </p>
    </div>
  </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const form = useForm({
  name: '',
  address: '',
  admin_name: '',
  admin_phone: '',
  admin_email: '',
  password: '',
  password_confirmation: '',
  website_hp: '',
})

function submit() {
  form.post(route('central.register.store'))
}
</script>
