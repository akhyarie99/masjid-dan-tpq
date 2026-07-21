<template>
  <Head :title="`Kartu Jamaah - ${jamaah.name}`" />

  <PrintLayout>
    <div class="max-w-xs mx-auto border-2 border-primary-600 rounded-xl p-5 text-center">
      <p class="text-xs font-semibold text-primary-700">{{ masjidName }}</p>
      <p class="text-[10px] text-slate-500 mb-3">KARTU JAMAAH DIGITAL</p>

      <div class="w-20 h-20 rounded-full bg-slate-100 mx-auto mb-3 overflow-hidden flex items-center justify-center text-2xl">
        <img v-if="jamaah.photo" :src="jamaah.photo" class="w-full h-full object-cover" alt="" />
        <span v-else>👤</span>
      </div>

      <p class="font-bold text-slate-900">{{ jamaah.name }}</p>
      <p class="text-xs text-slate-500">RT {{ jamaah.rt ?? '-' }} / RW {{ jamaah.rw ?? '-' }}</p>

      <img :src="qrCode" alt="QR Jamaah" class="w-24 h-24 mx-auto mt-3" />
    </div>
  </PrintLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import PrintLayout from '@/Layouts/PrintLayout.vue'

defineProps({
  jamaah: { type: Object, required: true },
  masjidName: { type: String, required: true },
  qrCode: { type: String, required: true },
})
</script>
