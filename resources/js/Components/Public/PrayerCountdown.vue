<template>
  <div class="text-center">
    <p v-if="nextPrayer" class="text-sm text-emerald-200">
      Menuju {{ nextPrayer.label }}
    </p>
    <p v-if="nextPrayer" class="text-3xl md:text-4xl font-bold text-gold-400 tabular-nums mt-1">
      {{ countdown }}
    </p>
    <p v-else class="text-sm text-emerald-200">Jadwal shalat belum tersedia</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePrayerTime } from '@/composables/usePrayerTime'

const props = defineProps({
  prayerTimes: { type: Object, default: null },
})

const scheduleRef = computed(() => props.prayerTimes)
const { nextPrayer, countdown } = usePrayerTime(scheduleRef)
</script>
