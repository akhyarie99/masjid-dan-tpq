<template>
  <Head title="Dashboard Wali" />

  <WaliLayout>
    <h1 class="text-xl font-bold text-[var(--text-primary)] mb-4">Ananda Saya</h1>

    <div class="card p-5 mb-4">
      <p class="text-sm font-medium text-[var(--text-primary)] mb-1">Notifikasi</p>
      <p class="text-xs text-[var(--text-muted)] mb-4">Dapatkan kabar saat ananda mengaji, nilai diperbarui, atau ada tagihan SPP.</p>

      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-[var(--text-primary)]">WhatsApp</p>
            <p class="text-xs text-[var(--text-muted)]">Kirim pesan ke nomor WA yang terdaftar.</p>
          </div>
          <button
            type="button"
            class="w-11 h-6 rounded-full transition-colors relative shrink-0"
            :class="notifyWhatsappLocal ? 'bg-primary-600' : 'bg-[var(--bg-muted)]'"
            @click="toggleWhatsapp"
          >
            <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white transition-all" :class="notifyWhatsappLocal ? 'left-[22px]' : 'left-0.5'" />
          </button>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-[var(--text-primary)]">Notifikasi Push (browser ini)</p>
            <p class="text-xs text-[var(--text-muted)]">{{ pushStatusLabel }}</p>
          </div>
          <button
            type="button"
            class="w-11 h-6 rounded-full transition-colors relative shrink-0 disabled:opacity-50"
            :class="notifyWebpushLocal ? 'bg-primary-600' : 'bg-[var(--bg-muted)]'"
            :disabled="pushBusy || !pushSupported"
            @click="togglePush"
          >
            <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white transition-all" :class="notifyWebpushLocal ? 'left-[22px]' : 'left-0.5'" />
          </button>
        </div>
        <p v-if="!pushSupported" class="text-xs text-yellow-600">Browser ini tidak mendukung notifikasi push.</p>
      </div>
    </div>

    <EmptyState v-if="students.length === 0" title="Tidak ada data santri terhubung dengan nomor HP ini." />

    <div v-else class="space-y-4">
      <div v-for="student in students" :key="student.id" class="card p-5">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-12 h-12 rounded-full bg-[var(--bg-muted)] flex items-center justify-center text-xl overflow-hidden shrink-0">
            <img v-if="student.photo" :src="student.photo" class="w-full h-full object-cover" alt="" />
            <span v-else>🧒</span>
          </div>
          <div>
            <p class="font-semibold text-[var(--text-primary)]">{{ student.name }}</p>
            <p class="text-xs text-[var(--text-muted)]">NIS {{ student.nis }} · {{ student.class?.name ?? 'Belum ada kelas' }}</p>
          </div>
        </div>

        <p class="text-sm font-medium text-[var(--text-primary)] mb-2">Progres Mengaji Terbaru</p>
        <EmptyState v-if="student.dailyProgress.length === 0" title="Belum ada catatan mengaji." />
        <ul v-else class="space-y-1.5 mb-4">
          <li v-for="(entry, index) in student.dailyProgress" :key="index" class="flex items-center justify-between text-sm">
            <span class="text-[var(--text-primary)]">{{ formatDate(entry.date) }} — {{ entry.summary }}</span>
            <span
              class="text-xs font-medium px-2 py-0.5 rounded-full"
              :class="entry.keterangan === 'lancar' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300'"
            >
              {{ entry.keterangan === 'lancar' ? 'Lancar' : 'Ulang' }}
            </span>
          </li>
        </ul>

        <p class="text-sm font-medium text-[var(--text-primary)] mb-2">Raport</p>
        <EmptyState v-if="student.reportCards.length === 0" title="Belum ada raport tersedia." />
        <ul v-else class="space-y-2">
          <li v-for="reportCard in student.reportCards" :key="reportCard.id" class="flex items-center justify-between text-sm">
            <span>{{ reportCard.semester?.name }}</span>
            <Link :href="route('wali.reportcard', reportCard.id)" class="text-primary-600 hover:underline">Lihat Raport</Link>
          </li>
        </ul>
      </div>
    </div>
  </WaliLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import WaliLayout from '@/Layouts/WaliLayout.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

const props = defineProps({
  students: { type: Array, default: () => [] },
  notifyWhatsapp: { type: Boolean, default: true },
  notifyWebpush: { type: Boolean, default: false },
  vapidPublicKey: { type: String, default: null },
})

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}

const notifyWhatsappLocal = ref(props.notifyWhatsapp)
const notifyWebpushLocal = ref(props.notifyWebpush)
const pushBusy = ref(false)
const pushSupported = 'serviceWorker' in navigator && 'PushManager' in window

const pushStatusLabel = computed(() => {
  if (!pushSupported) return 'Tidak didukung di browser ini.'
  if (pushBusy.value) return 'Memproses...'
  return notifyWebpushLocal.value ? 'Aktif di browser ini.' : 'Nonaktif — aktifkan untuk terima notifikasi langsung di browser.'
})

function toggleWhatsapp() {
  notifyWhatsappLocal.value = !notifyWhatsappLocal.value
  savePreferences()
}

function savePreferences() {
  router.post(route('wali.notifications.update'), {
    notify_whatsapp: notifyWhatsappLocal.value,
    notify_webpush: notifyWebpushLocal.value,
  }, { preserveScroll: true, preserveState: true })
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const raw = window.atob(base64)
  return Uint8Array.from([...raw].map((char) => char.charCodeAt(0)))
}

async function togglePush() {
  if (!pushSupported || pushBusy.value) return

  pushBusy.value = true
  try {
    if (notifyWebpushLocal.value) {
      await unsubscribePush()
    } else {
      await subscribePush()
    }
  } finally {
    pushBusy.value = false
  }
}

async function subscribePush() {
  if (!props.vapidPublicKey) {
    alert('Notifikasi push belum diaktifkan oleh admin masjid.')
    return
  }

  const permission = await Notification.requestPermission()
  if (permission !== 'granted') return

  const registration = await navigator.serviceWorker.ready
  const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(props.vapidPublicKey),
  })

  router.post(route('wali.push.subscribe'), subscription.toJSON(), {
    preserveScroll: true,
    onSuccess: () => { notifyWebpushLocal.value = true },
  })
}

async function unsubscribePush() {
  const registration = await navigator.serviceWorker.ready
  const subscription = await registration.pushManager.getSubscription()

  if (subscription) {
    const endpoint = subscription.endpoint
    await subscription.unsubscribe()
    router.post(route('wali.push.unsubscribe'), { endpoint }, {
      preserveScroll: true,
      onSuccess: () => { notifyWebpushLocal.value = false },
    })
  } else {
    notifyWebpushLocal.value = false
  }
}

onMounted(async () => {
  if (!pushSupported) return
  const registration = await navigator.serviceWorker.ready
  const subscription = await registration.pushManager.getSubscription()
  notifyWebpushLocal.value = !!subscription && props.notifyWebpush
})
</script>
