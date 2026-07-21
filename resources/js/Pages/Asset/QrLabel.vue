<template>
  <Head :title="`QR Aset - ${asset.name}`" />

  <PrintLayout>
    <div class="text-center">
      <p class="text-sm text-slate-500">{{ masjidName }}</p>
      <h1 class="text-xl font-bold mt-1">{{ asset.name }}</h1>
      <p class="text-sm text-slate-500 font-mono">{{ asset.asset_code }}</p>

      <img :src="qrCode" alt="QR Code" class="w-48 h-48 mx-auto my-6" />

      <p class="text-sm text-slate-600">📍 {{ asset.location }}</p>
    </div>
  </PrintLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import PrintLayout from '@/Layouts/PrintLayout.vue'

defineProps({
  asset: { type: Object, required: true },
  masjidName: { type: String, required: true },
  qrCode: { type: String, required: true },
})
</script>
