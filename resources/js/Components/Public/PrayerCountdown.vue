<template>
  <div class="text-center">
    <p v-if="nextPrayer" class="text-sm text-emerald-200">
      Menuju {{ nextPrayer.label }}
    </p>
    <p v-if="nextPrayer" class="text-3xl md:text-4xl font-bold text-gold-400 tabular-nums mt-1">
      {{ countdown }}
    </p>
    <p v-else class="text-sm text-emerald-200">Jadwal shalat belum tersedia</p>

    <div
      v-if="iqomahInfo"
      class="inline-block mt-3 px-4 py-2 rounded-xl font-bold tabular-nums transition-all"
      :class="iqomahInfo.isTime
        ? 'bg-gold-400 text-emerald-950 animate-pulse text-base md:text-xl'
        : 'bg-white/10 text-gold-300 text-xs md:text-sm'"
    >
      <template v-if="iqomahInfo.isTime">🕌 Waktunya Iqomah — {{ iqomahInfo.prayerLabel }}</template>
      <template v-else>Iqomah {{ iqomahInfo.prayerLabel }} dalam {{ iqomahInfo.countdown }}</template>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePrayerTime } from '@/composables/usePrayerTime'

const props = defineProps({
  prayerTimes: { type: Object, default: null },
  iqomahOffsetMinutes: { type: Number, default: 0 },
})

const scheduleRef = computed(() => props.prayerTimes)
const { nextPrayer, countdown, iqomahInfo } = usePrayerTime(scheduleRef, computed(() => props.iqomahOffsetMinutes))
</script>
