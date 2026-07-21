import { computed, onMounted, onUnmounted, ref, unref } from 'vue'
import dayjs from 'dayjs'

const LABELS = {
  fajr: 'Subuh',
  sunrise: 'Terbit',
  dhuhr: 'Dzuhur',
  asr: 'Ashar',
  maghrib: 'Maghrib',
  isha: 'Isya',
}

export function usePrayerTime(prayerTimes) {
  const now = ref(dayjs())
  let timer

  onMounted(() => {
    timer = setInterval(() => {
      now.value = dayjs()
    }, 1000)
  })

  onUnmounted(() => clearInterval(timer))

  const prayers = computed(() => {
    const times = unref(prayerTimes)
    if (!times) return []

    return Object.keys(LABELS).map((key) => ({
      key,
      label: LABELS[key],
      time: times[key],
    }))
  })

  function toToday(time) {
    const [h, m, s] = time.split(':').map(Number)
    return now.value.hour(h).minute(m).second(s || 0).millisecond(0)
  }

  const orderedPrayers = computed(() => prayers.value.filter((p) => p.key !== 'sunrise'))

  const activePrayer = computed(() => {
    let active = null
    for (const p of orderedPrayers.value) {
      if (!toToday(p.time).isAfter(now.value)) {
        active = p.key
      }
    }
    return active
  })

  const nextPrayer = computed(() => {
    for (const p of orderedPrayers.value) {
      if (toToday(p.time).isAfter(now.value)) return p
    }
    return orderedPrayers.value[0] ?? null
  })

  const countdown = computed(() => {
    if (!nextPrayer.value) return '--:--:--'

    let target = toToday(nextPrayer.value.time)
    if (!target.isAfter(now.value)) target = target.add(1, 'day')

    const diff = target.diff(now.value, 'second')
    const h = String(Math.floor(diff / 3600)).padStart(2, '0')
    const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0')
    const s = String(diff % 60).padStart(2, '0')

    return `${h}:${m}:${s}`
  })

  return { now, prayers, activePrayer, nextPrayer, countdown }
}
