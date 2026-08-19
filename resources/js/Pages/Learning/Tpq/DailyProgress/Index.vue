<template>
  <Head title="Mengaji Harian" />

  <AdminLayout title="Mengaji Harian">
    <TpqSubNav />
    <PageHeader title="Mengaji Harian" description="Scan QR di buku mutabaah santri, atau cari namanya kalau bukunya lupa dibawa." />

    <AppCard class="max-w-lg">
      <AppInput v-model="date" type="date" label="Tanggal" class="mb-5" @change="onDateChange" />

      <button
        type="button"
        class="w-full flex items-center justify-center gap-2 py-4 rounded-xl bg-primary-600 text-white font-semibold hover:bg-primary-700 transition-colors mb-5"
        @click="scannerOpen = true"
      >
        <QrCodeIcon class="w-5 h-5" /> Scan QR Santri
      </button>

      <div class="relative mb-2">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[var(--border)]" /></div>
        <div class="relative flex justify-center text-xs"><span class="bg-[var(--bg-surface)] px-2 text-[var(--text-muted)]">atau cari nama</span></div>
      </div>

      <div class="relative">
        <input
          v-model="query"
          type="text"
          placeholder="Ketik nama atau NIS santri..."
          class="input"
          @input="scheduleSearch"
        />

        <div v-if="results.length > 0" class="mt-2 border border-[var(--border)] rounded-xl overflow-hidden divide-y divide-[var(--border)]">
          <button
            v-for="student in results"
            :key="student.id"
            type="button"
            class="w-full flex items-center gap-3 p-3 text-left hover:bg-[var(--bg-muted)] transition-colors"
            @click="goToStudent(student.id)"
          >
            <div class="w-9 h-9 rounded-full bg-[var(--bg-muted)] flex items-center justify-center text-sm overflow-hidden shrink-0">
              <img v-if="student.photo" :src="student.photo" class="w-full h-full object-cover" alt="" />
              <span v-else>🧒</span>
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-medium text-[var(--text-primary)] truncate">{{ student.name }}</p>
              <p class="text-xs text-[var(--text-muted)]">{{ student.nis }} · {{ student.class ?? 'Belum ada kelas' }}</p>
            </div>
          </button>
        </div>
        <p v-else-if="searched && query.length > 0" class="text-sm text-[var(--text-muted)] mt-2">Santri tidak ditemukan.</p>
      </div>
    </AppCard>

    <p class="max-w-lg mt-4 text-sm text-center space-x-3">
      <Link :href="route('admin.tpq.daily-progress.kelas.index')" class="text-primary-600 hover:underline">
        Atau pilih dari daftar kelas
      </Link>
      <span class="text-[var(--text-muted)]">·</span>
      <Link :href="route('admin.tpq.daily-progress.recap')" class="text-primary-600 hover:underline">
        Lihat Rekap
      </Link>
    </p>

    <QrScannerModal :open="scannerOpen" @close="scannerOpen = false" @decode="onDecode" />
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { QrCode as QrCodeIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import TpqSubNav from '@/Components/Shared/TpqSubNav.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import QrScannerModal from '@/Components/Tpq/QrScannerModal.vue'

const props = defineProps({
  date: { type: String, required: true },
})

const date = ref(props.date)
const query = ref('')
const results = ref([])
const searched = ref(false)
const scannerOpen = ref(false)

const UUID_RE = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i

function onDateChange() {
  router.get(route('admin.tpq.daily-progress.index'), { date: date.value }, { preserveState: true })
}

let searchTimer
function scheduleSearch() {
  clearTimeout(searchTimer)
  if (query.value.trim().length < 2) {
    results.value = []
    searched.value = false
    return
  }
  searchTimer = setTimeout(runSearch, 350)
}

async function runSearch() {
  const response = await fetch(`${route('admin.tpq.daily-progress.search')}?q=${encodeURIComponent(query.value.trim())}`, {
    headers: { Accept: 'application/json' },
  })
  const data = await response.json()
  results.value = data.students
  searched.value = true
}

function goToStudent(studentId) {
  router.get(route('admin.tpq.daily-progress.santri', studentId), { date: date.value })
}

function onDecode(value) {
  scannerOpen.value = false
  const id = value.trim()

  if (!UUID_RE.test(id)) {
    alert('QR tidak dikenali. Coba scan ulang atau cari nama santri secara manual.')
    return
  }

  goToStudent(id)
}
</script>
