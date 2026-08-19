<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[70] bg-black/90 flex flex-col items-center justify-center p-4">
      <button type="button" class="absolute top-4 right-4 text-white/80 hover:text-white p-2" @click="close">
        <XIcon class="w-7 h-7" />
      </button>

      <p class="text-white text-sm mb-4">Arahkan kamera ke QR di buku mutabaah santri</p>

      <div class="relative w-full max-w-sm aspect-square rounded-2xl overflow-hidden bg-black">
        <video ref="videoEl" class="w-full h-full object-cover" />
        <div class="absolute inset-6 border-2 border-white/60 rounded-xl pointer-events-none" />
      </div>

      <p v-if="error" class="text-red-400 text-sm mt-4 text-center max-w-sm">{{ error }}</p>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue'
import { X as XIcon } from 'lucide-vue-next'
import QrScanner from 'qr-scanner'

const props = defineProps({
  open: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'decode'])

const videoEl = ref(null)
const error = ref('')
let scanner = null

async function start() {
  error.value = ''
  await nextTick()
  if (!videoEl.value) return

  scanner = new QrScanner(
    videoEl.value,
    (result) => emit('decode', result.data),
    { highlightScanRegion: false, highlightCodeOutline: false, preferredCamera: 'environment' },
  )

  try {
    await scanner.start()
  } catch {
    error.value = 'Tidak bisa mengakses kamera. Pastikan izin kamera diaktifkan, atau gunakan pencarian nama.'
  }
}

function stop() {
  scanner?.stop()
  scanner?.destroy()
  scanner = null
}

function close() {
  stop()
  emit('close')
}

watch(() => props.open, (isOpen) => {
  if (isOpen) start()
  else stop()
})
</script>
