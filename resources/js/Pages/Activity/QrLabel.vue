<template>
  <Head :title="`QR Presensi - ${activity.name}`" />

  <PrintLayout>
    <div class="text-center">
      <h1 class="text-xl font-bold">{{ activity.name }}</h1>
      <p class="text-sm text-slate-500">{{ formatDate(activity.start_at) }} · {{ activity.location }}</p>

      <img :src="qrCode" alt="QR Presensi" class="w-56 h-56 mx-auto my-6" />

      <p class="text-sm text-slate-600">Scan untuk presensi kehadiran</p>
    </div>
  </PrintLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import PrintLayout from '@/Layouts/PrintLayout.vue'

defineProps({
  activity: { type: Object, required: true },
  qrCode: { type: String, required: true },
})

function formatDate(value) {
  return dayjs(value).format('dddd, DD MMM YYYY, HH:mm')
}
</script>
