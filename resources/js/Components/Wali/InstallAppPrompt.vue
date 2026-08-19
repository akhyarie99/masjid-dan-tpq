<template>
  <div v-if="visible" class="card p-4 mb-4">
    <div class="flex items-start gap-3">
      <div class="w-9 h-9 rounded-lg bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center shrink-0">
        <DownloadIcon class="w-5 h-5 text-primary-600" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-[var(--text-primary)]">Pasang Portal Wali di HP Anda</p>
        <p class="text-xs text-[var(--text-muted)] mt-0.5">
          Supaya lebih mudah dibuka dan bisa terima notifikasi — termasuk di iPhone (Safari), yang notifikasinya
          <span class="font-medium">hanya jalan setelah aplikasi ini dipasang</span>.
        </p>

        <AppButton v-if="!isIos" size="sm" class="mt-3" :loading="installing" @click="install">
          Pasang Aplikasi
        </AppButton>

        <template v-else>
          <button type="button" class="text-primary-600 text-sm font-medium hover:underline mt-3" @click="showGuide = !showGuide">
            {{ showGuide ? 'Sembunyikan cara pasang' : 'Lihat cara pasang di iPhone' }}
          </button>
          <ol v-if="showGuide" class="text-xs text-[var(--text-muted)] mt-2 space-y-1 list-decimal list-inside">
            <li>Buka halaman ini di <strong>Safari</strong> (bukan Chrome/aplikasi lain).</li>
            <li>Tap ikon <strong>Bagikan</strong> (kotak dengan panah ke atas) di bar bawah.</li>
            <li>Pilih <strong>"Add to Home Screen" / "Tambah ke Layar Utama"</strong>.</li>
            <li>Tap <strong>Tambah/Add</strong>.</li>
            <li>Buka aplikasinya dari ikon di layar utama (bukan dari Safari lagi), lalu aktifkan notifikasi.</li>
          </ol>
        </template>
      </div>
      <button type="button" class="text-[var(--text-muted)] hover:text-[var(--text-primary)] shrink-0" @click="dismiss">
        <XIcon class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { Download as DownloadIcon, X as XIcon } from 'lucide-vue-next'
import AppButton from '@/Components/UI/AppButton.vue'

const STORAGE_KEY = 'wali-install-prompt-dismissed'

const visible = ref(false)
const isIos = ref(false)
const showGuide = ref(false)
const installing = ref(false)
let deferredPrompt = null

function isStandalone() {
  return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true
}

function dismiss() {
  visible.value = false
  localStorage.setItem(STORAGE_KEY, '1')
}

async function install() {
  if (!deferredPrompt) return
  installing.value = true
  deferredPrompt.prompt()
  await deferredPrompt.userChoice
  installing.value = false
  visible.value = false
  deferredPrompt = null
}

onMounted(() => {
  if (isStandalone() || localStorage.getItem(STORAGE_KEY)) return

  isIos.value = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase()) && !window.MSStream

  if (isIos.value) {
    visible.value = true
    return
  }

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault()
    deferredPrompt = event
    visible.value = true
  })

  window.addEventListener('appinstalled', () => {
    visible.value = false
    deferredPrompt = null
  })
})
</script>
