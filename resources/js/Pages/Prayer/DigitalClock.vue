<template>
  <Head :title="`Jam Digital - ${masjid.name}`" />

  <div
    class="min-h-screen flex flex-col bg-cover bg-center"
    :class="masjid.background_url ? '' : 'bg-gradient-to-b from-emerald-950 to-slate-950'"
    :style="heroStyle"
  >
    <div v-if="masjid.background_url" class="fixed inset-0 bg-gradient-to-b from-black/70 via-black/55 to-emerald-950/90" />

    <div class="relative z-10 flex-1 flex flex-col">
      <!-- Header: Logo + Nama Masjid -->
      <div class="text-center pt-6 px-4">
        <img v-if="masjid.logo_url" :src="masjid.logo_url" alt="Logo masjid" class="w-14 h-14 md:w-16 md:h-16 mx-auto mb-2 object-contain" />
        <div class="text-gold-400 font-arabic text-xl md:text-2xl">{{ masjid.name }}</div>
      </div>

      <!-- Jam Digital Besar -->
      <div class="flex-1 flex flex-col items-center justify-center gap-4">
        <div class="text-[12vw] md:text-[8vw] font-bold text-white tracking-widest tabular-nums">{{ currentTime }}</div>
        <div class="text-lg md:text-2xl text-emerald-300">{{ hijriDate }} &nbsp;|&nbsp; {{ miladiDate }}</div>
        <div v-if="nextPrayer" class="text-base md:text-xl text-gold-400 tabular-nums">
          {{ nextPrayer.label }} dalam {{ countdown }}
        </div>
      </div>

      <!-- Grid Waktu Shalat -->
      <div class="grid grid-cols-3 md:grid-cols-6 gap-2 p-4 md:p-6">
        <div
          v-for="prayer in prayers"
          :key="prayer.key"
          class="rounded-xl p-3 md:p-4 text-center backdrop-blur-sm transition-all"
          :class="activePrayer === prayer.key
            ? 'bg-primary-600 text-white shadow-[0_0_25px_rgba(22,163,74,0.6)]'
            : 'bg-white/10 text-emerald-100'"
        >
          <p class="text-xs md:text-sm opacity-80">{{ prayer.label }}</p>
          <p class="text-lg md:text-2xl font-bold tabular-nums mt-1">{{ prayer.time ?? '--:--' }}</p>
        </div>
      </div>

      <!-- Ticker Berjalan -->
      <div class="bg-black/40 backdrop-blur-sm py-2 overflow-hidden border-t border-white/10">
        <div class="animate-marquee whitespace-nowrap text-emerald-100 text-sm md:text-base">
          {{ tickerText }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import moment from 'moment-hijri'
import { usePrayerTime } from '@/composables/usePrayerTime'

const props = defineProps({
  masjid: { type: Object, required: true },
  schedule: { type: Object, default: null },
  tickerItems: { type: Array, default: () => [] },
})

const scheduleRef = computed(() => props.schedule)
const { now, prayers, activePrayer, nextPrayer, countdown } = usePrayerTime(scheduleRef)

const currentTime = computed(() => now.value.format('HH:mm:ss'))
const miladiDate = computed(() => dayjs(now.value.toDate()).format('dddd, DD MMMM YYYY'))
const hijriDate = computed(() => moment(now.value.toDate()).format('iD iMMMM iYYYY') + ' H')

const heroStyle = computed(() => props.masjid.background_url
  ? { backgroundImage: `url(${props.masjid.background_url})` }
  : {})

const tickerText = computed(() => {
  const items = props.tickerItems.length > 0
    ? props.tickerItems
    : ['Selamat datang di ' + props.masjid.name]
  return items.join('     •     ')
})
</script>
