<template>
  <div class="text-center">
    <div class="text-5xl md:text-7xl font-bold text-white tracking-widest tabular-nums">{{ time }}</div>
    <div class="mt-2 text-sm md:text-base text-emerald-200">{{ hijriDate }} &nbsp;|&nbsp; {{ miladiDate }}</div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import moment from 'moment-hijri'

const time = ref('')
const hijriDate = ref('')
const miladiDate = ref('')

let timer

function tick() {
  const now = moment()
  time.value = now.format('HH:mm:ss')
  miladiDate.value = now.format('dddd, DD MMMM YYYY')
  hijriDate.value = now.format('iD iMMMM iYYYY') + ' H'
}

onMounted(() => {
  tick()
  timer = setInterval(tick, 1000)
})

onUnmounted(() => clearInterval(timer))
</script>
