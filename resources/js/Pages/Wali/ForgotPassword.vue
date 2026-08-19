<template>
  <Head title="Lupa Password" />

  <div class="min-h-screen flex items-center justify-center p-4 bg-[var(--bg-base)]">
    <div class="w-full max-w-sm card p-6">
      <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-xl bg-primary-600 flex items-center justify-center text-white text-xl mx-auto mb-3">🔑</div>
        <h1 class="text-lg font-bold text-[var(--text-primary)]">Lupa Password</h1>
        <p class="text-sm text-[var(--text-muted)] mt-1">Masukkan nomor HP wali yang terdaftar.</p>
      </div>

      <p v-if="successMessage" class="text-sm text-green-600 text-center mb-4">{{ successMessage }}</p>

      <!-- Langkah 1: cari akun -->
      <form v-if="!channels" class="space-y-4" @submit.prevent="find">
        <AppInput v-model="phone" label="Nomor HP Wali" required :error="findError" />
        <AppButton type="submit" class="w-full justify-center" :loading="finding">Lanjut</AppButton>
      </form>

      <!-- Langkah 2: pilih channel -->
      <div v-else class="space-y-3">
        <p class="text-sm text-[var(--text-primary)]">Kirim link reset password ke:</p>

        <button
          v-if="channels.whatsapp"
          type="button"
          class="w-full flex items-center justify-between p-3 rounded-lg border border-[var(--border)] hover:bg-[var(--bg-muted)] transition-colors text-left"
          :disabled="sending"
          @click="send('whatsapp')"
        >
          <span>
            <span class="block text-sm font-medium text-[var(--text-primary)]">WhatsApp</span>
            <span class="block text-xs text-[var(--text-muted)]">{{ channels.whatsapp }}</span>
          </span>
          <ChevronRightIcon class="w-4 h-4 text-[var(--text-muted)]" />
        </button>

        <button
          v-if="channels.email"
          type="button"
          class="w-full flex items-center justify-between p-3 rounded-lg border border-[var(--border)] hover:bg-[var(--bg-muted)] transition-colors text-left"
          :disabled="sending"
          @click="send('email')"
        >
          <span>
            <span class="block text-sm font-medium text-[var(--text-primary)]">Email</span>
            <span class="block text-xs text-[var(--text-muted)]">{{ channels.email }}</span>
          </span>
          <ChevronRightIcon class="w-4 h-4 text-[var(--text-muted)]" />
        </button>

        <p v-if="!channels.whatsapp && !channels.email" class="text-sm text-[var(--text-muted)]">
          Tidak ada nomor WA atau email tersimpan untuk akun ini. Hubungi pengurus TPQ untuk reset manual.
        </p>

        <button type="button" class="text-primary-600 text-sm hover:underline mt-2" @click="channels = null">
          Ganti nomor HP
        </button>
      </div>

      <p class="text-xs text-center mt-4">
        <Link :href="route('wali.login')" class="text-primary-600 hover:underline">Kembali ke halaman masuk</Link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ChevronRight as ChevronRightIcon } from 'lucide-vue-next'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const phone = ref('')
const channels = ref(null)
const finding = ref(false)
const sending = ref(false)
const findError = ref('')
const successMessage = ref('')

async function find() {
  findError.value = ''
  finding.value = true
  try {
    const response = await fetch(route('wali.forgot-password.find'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
      body: JSON.stringify({ phone: phone.value }),
    })
    const data = await response.json()
    if (!data.found) {
      findError.value = 'Nomor HP ini tidak terdaftar sebagai wali.'
      return
    }
    channels.value = { whatsapp: data.whatsapp, email: data.email }
  } catch {
    findError.value = 'Terjadi kesalahan. Coba lagi.'
  } finally {
    finding.value = false
  }
}

async function send(channel) {
  sending.value = true
  successMessage.value = ''
  try {
    const response = await fetch(route('wali.forgot-password.send'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
      body: JSON.stringify({ phone: phone.value, channel }),
    })
    if (response.ok) {
      successMessage.value = channel === 'whatsapp'
        ? 'Link reset password sudah dikirim ke WhatsApp Anda.'
        : 'Link reset password sudah dikirim ke email Anda.'
      channels.value = null
      phone.value = ''
    }
  } finally {
    sending.value = false
  }
}

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content
    ?? decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '')
}
</script>
