<template>
  <Head title="Rekap Presensi Staf" />

  <AdminLayout title="Rekap Presensi Staf">
    <PageHeader title="Rekap Presensi Staf" :description="monthLabel">
      <template #actions>
        <a :href="exportUrl" class="btn-secondary"><FileTextIcon class="w-4 h-4" /> Export Excel</a>
        <Link :href="route('admin.staff-attendance.index', { month: prevMonth.month, year: prevMonth.year })" class="btn-secondary">
          <ChevronLeftIcon class="w-4 h-4" />
        </Link>
        <Link :href="route('admin.staff-attendance.index', { month: nextMonth.month, year: nextMonth.year })" class="btn-secondary">
          <ChevronRightIcon class="w-4 h-4" />
        </Link>
      </template>
    </PageHeader>

    <AppCard v-if="recap.length === 0" class="text-center text-sm text-[var(--text-muted)]">
      Belum ada staf aktif atau data presensi untuk bulan ini.
    </AppCard>

    <AppCard v-else :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th class="sticky left-0 bg-[var(--bg-muted)]">Staf</th>
              <th v-for="day in daysInMonth" :key="day" class="text-center !px-2">{{ day }}</th>
              <th class="text-center">Hadir</th>
              <th class="text-center">Lengkap</th>
              <th class="text-center">%</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in recap" :key="row.user.id">
              <td class="sticky left-0 bg-[var(--bg-surface)] whitespace-nowrap">{{ row.user.name }}</td>
              <td v-for="day in daysInMonth" :key="day" class="text-center !px-1">
                <button
                  type="button"
                  class="inline-block w-5 h-5 rounded relative"
                  :class="cellClass(row.days[dateKey(day)])"
                  :title="cellTitle(row.days[dateKey(day)])"
                  :disabled="!row.days[dateKey(day)]"
                  @click="showDetail(row.days[dateKey(day)])"
                >
                  <span
                    v-if="hasWarning(row.days[dateKey(day)])"
                    class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-red-600"
                  />
                </button>
              </td>
              <td class="text-center">{{ row.present_count }}</td>
              <td class="text-center">{{ row.complete_count }}</td>
              <td class="text-center font-medium" :class="row.percent >= 75 ? 'text-green-600' : 'text-red-500'">{{ row.percent }}%</td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <div class="flex flex-wrap items-center gap-4 mt-4 text-xs text-[var(--text-muted)]">
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-500 inline-block" /> Masuk & Pulang lengkap</span>
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-yellow-400 inline-block" /> Cuma Masuk (belum Pulang)</span>
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-[var(--bg-muted)] inline-block" /> Tidak ada presensi</span>
      <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-600 inline-block" /> GPS palsu / verifikasi wajah gagal</span>
    </div>

    <AppModal :show="detailOpen" title="Detail Presensi" size="lg" @close="detailOpen = false">
      <div v-if="detailLoading" class="text-sm text-[var(--text-muted)] py-4 text-center">Memuat...</div>
      <div v-else-if="detail" class="space-y-4 text-sm">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-[var(--text-muted)]">Staf</p>
            <p class="font-medium text-[var(--text-primary)]">{{ detail.user }}</p>
          </div>
          <div>
            <p class="text-[var(--text-muted)]">Tanggal</p>
            <p class="font-medium text-[var(--text-primary)]">{{ dayjs(detail.date).format('dddd, D MMMM YYYY') }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="border border-[var(--border)] rounded-lg p-3">
            <p class="font-medium text-[var(--text-primary)] mb-2">Masuk — {{ detail.clock_in ?? '-' }}</p>
            <img v-if="detail.clock_in_photo_url" :src="detail.clock_in_photo_url" alt="Foto masuk" class="w-full rounded-lg mb-2 max-h-48 object-cover" />
            <p class="text-xs text-[var(--text-muted)]">Lokasi: {{ detail.clock_in_location ?? '-' }}</p>
            <a
              v-if="detail.clock_in_lat"
              :href="`https://www.google.com/maps?q=${detail.clock_in_lat},${detail.clock_in_lng}`"
              target="_blank"
              class="text-xs text-primary-600 hover:underline"
            >Lihat titik GPS</a>
            <p class="text-xs mt-1" :class="detail.clock_in_is_mock_location ? 'text-red-500' : 'text-green-600'">
              {{ detail.clock_in_is_mock_location ? '⚠️ GPS terdeteksi palsu' : 'GPS asli' }}
            </p>
            <p v-if="detail.clock_in" class="text-xs" :class="detail.clock_in_liveness_verified ? 'text-green-600' : 'text-red-500'">
              {{ detail.clock_in_liveness_verified ? 'Wajah terverifikasi' : '⚠️ Verifikasi wajah gagal' }}
            </p>
          </div>

          <div class="border border-[var(--border)] rounded-lg p-3">
            <p class="font-medium text-[var(--text-primary)] mb-2">Pulang — {{ detail.clock_out ?? '-' }}</p>
            <img v-if="detail.clock_out_photo_url" :src="detail.clock_out_photo_url" alt="Foto pulang" class="w-full rounded-lg mb-2 max-h-48 object-cover" />
            <p class="text-xs text-[var(--text-muted)]">Lokasi: {{ detail.clock_out_location ?? '-' }}</p>
            <a
              v-if="detail.clock_out_lat"
              :href="`https://www.google.com/maps?q=${detail.clock_out_lat},${detail.clock_out_lng}`"
              target="_blank"
              class="text-xs text-primary-600 hover:underline"
            >Lihat titik GPS</a>
            <p v-if="detail.clock_out" class="text-xs mt-1" :class="detail.clock_out_is_mock_location ? 'text-red-500' : 'text-green-600'">
              {{ detail.clock_out_is_mock_location ? '⚠️ GPS terdeteksi palsu' : 'GPS asli' }}
            </p>
            <p v-if="detail.clock_out" class="text-xs" :class="detail.clock_out_liveness_verified ? 'text-green-600' : 'text-red-500'">
              {{ detail.clock_out_liveness_verified ? 'Wajah terverifikasi' : '⚠️ Verifikasi wajah gagal' }}
            </p>
          </div>
        </div>
      </div>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { ChevronLeft as ChevronLeftIcon, ChevronRight as ChevronRightIcon, FileText as FileTextIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppModal from '@/Components/UI/AppModal.vue'

const props = defineProps({
  month: { type: Number, required: true },
  year: { type: Number, required: true },
  recap: { type: Array, default: () => [] },
})

const exportUrl = computed(() => route('admin.staff-attendance.export', { month: props.month, year: props.year }))

const detailOpen = ref(false)
const detailLoading = ref(false)
const detail = ref(null)

async function showDetail(cell) {
  if (!cell || !cell.id) return
  detailOpen.value = true
  detailLoading.value = true
  detail.value = null
  try {
    const response = await fetch(route('admin.staff-attendance.detail', cell.id), {
      headers: { Accept: 'application/json' },
    })
    detail.value = await response.json()
  } finally {
    detailLoading.value = false
  }
}

const monthLabel = computed(() => dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-01`).format('MMMM YYYY'))
const daysInMonth = computed(() => dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-01`).daysInMonth())

const prevMonth = computed(() => {
  const d = dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-01`).subtract(1, 'month')
  return { month: d.month() + 1, year: d.year() }
})
const nextMonth = computed(() => {
  const d = dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-01`).add(1, 'month')
  return { month: d.month() + 1, year: d.year() }
})

function dateKey(day) {
  return dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-${String(day).padStart(2, '0')}`).format('YYYY-MM-DD')
}

function cellClass(cell) {
  if (!cell || !cell.clock_in) return 'bg-[var(--bg-muted)]'
  return cell.clock_out ? 'bg-green-500' : 'bg-yellow-400'
}

function cellTitle(cell) {
  if (!cell || !cell.clock_in) return 'Tidak ada presensi'
  return `Masuk: ${cell.clock_in}${cell.clock_out ? ' — Pulang: ' + cell.clock_out : ' — belum pulang'}`
}

function hasWarning(cell) {
  if (!cell) return false
  return cell.clock_in_mock || cell.clock_out_mock || cell.clock_in_liveness_ok === false || cell.clock_out_liveness_ok === false
}
</script>
