<template>
  <Head title="Jadwal Imam" />

  <AdminLayout title="Jadwal Imam">
    <PageHeader title="Jadwal Imam" :description="monthLabel">
      <template #actions>
        <a :href="exportUrl" class="btn-secondary"><FileTextIcon class="w-4 h-4" /> Export PDF</a>
        <AppButton :loading="notifying" @click="notifyAll"><SendIcon class="w-4 h-4" /> Kirim Notifikasi ke Semua Imam</AppButton>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th v-for="prayer in prayers" :key="prayer">{{ prayerLabel(prayer) }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="day in daysInMonth" :key="day" :class="isToday(day) ? 'bg-primary-50 dark:bg-primary-900/10' : ''">
              <td class="whitespace-nowrap font-medium">{{ formatDay(day) }}</td>
              <td v-for="prayer in prayers" :key="prayer">
                <button
                  class="text-sm px-2 py-1 rounded-lg hover:bg-[var(--bg-muted)] w-full text-left"
                  :class="cellClass(day, prayer)"
                  @click="openCell(day, prayer)"
                >
                  {{ cellLabel(day, prayer) }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <AppModal :show="activeCell !== null" title="Atur Jadwal Imam" @close="activeCell = null">
      <div v-if="activeCell" class="space-y-4">
        <p class="text-sm text-[var(--text-muted)]">{{ formatDay(activeCell.day) }} — {{ prayerLabel(activeCell.prayer) }}</p>

        <AppSelect v-model="cellForm.imam_id" label="Imam" required :options="imams.map((i) => ({ label: `${i.name} (${typeLabel(i.type)})`, value: i.id }))" />

        <template v-if="activeCell.prayer === 'jumuah'">
          <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
            <input v-model="cellForm.is_khatib" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
            Sekaligus Khatib
          </label>
          <AppInput v-model="cellForm.khutbah_theme" label="Tema Khutbah" />
        </template>

        <div v-if="existingSchedule" class="pt-2 border-t border-[var(--border)]">
          <p class="text-xs text-[var(--text-muted)] mb-2">Atau tetapkan imam pengganti untuk jadwal ini:</p>
          <div class="flex gap-2">
            <AppSelect v-model="substituteId" :options="imams.map((i) => ({ label: i.name, value: i.id }))" placeholder="Pilih pengganti" class="flex-1" />
            <AppButton size="sm" variant="secondary" @click="submitSubstitute">Ganti</AppButton>
          </div>
        </div>
      </div>
      <template #footer>
        <AppButton variant="secondary" @click="activeCell = null">Batal</AppButton>
        <AppButton :loading="cellFormProcessing" @click="submitCell">Simpan</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { FileText as FileTextIcon, Send as SendIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  month: { type: Number, required: true },
  year: { type: Number, required: true },
  prayers: { type: Array, required: true },
  schedules: { type: Array, default: () => [] },
  imams: { type: Array, default: () => [] },
})

const notifying = ref(false)
const activeCell = ref(null)
const cellForm = ref({ imam_id: '', is_khatib: false, khutbah_theme: '' })
const cellFormProcessing = ref(false)
const substituteId = ref('')

const monthLabel = computed(() => dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-01`).format('MMMM YYYY'))
const daysInMonth = computed(() => dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-01`).daysInMonth())
const exportUrl = computed(() => route('admin.prayer.imam-schedule.export', { month: props.month, year: props.year }))

function dateFor(day) {
  return dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-${String(day).padStart(2, '0')}`)
}

function formatDay(day) {
  return dateFor(day).format('ddd, DD MMM')
}

function isToday(day) {
  return dateFor(day).isSame(dayjs(), 'day')
}

function findSchedule(day, prayer) {
  const dateStr = dateFor(day).format('YYYY-MM-DD')
  return props.schedules.find((s) => s.date.slice(0, 10) === dateStr && s.prayer === prayer)
}

const existingSchedule = computed(() => activeCell.value ? findSchedule(activeCell.value.day, activeCell.value.prayer) : null)

function cellLabel(day, prayer) {
  const schedule = findSchedule(day, prayer)
  if (!schedule) return '+ Tambah'
  return schedule.is_substituted ? (schedule.substitute_imam?.name ?? '-') : (schedule.imam?.name ?? '-')
}

function cellClass(day, prayer) {
  const schedule = findSchedule(day, prayer)
  if (!schedule) return 'text-[var(--text-muted)]'
  if (schedule.is_substituted) return 'text-yellow-600 font-medium'
  return schedule.imam?.type === 'tetap' ? 'text-green-600 font-medium' : 'text-blue-600 font-medium'
}

function prayerLabel(prayer) {
  return { fajr: 'Subuh', dhuhr: 'Dzuhur', asr: 'Ashar', maghrib: 'Maghrib', isha: 'Isya', jumuah: 'Jumat' }[prayer] ?? prayer
}

function typeLabel(type) {
  return { tetap: 'Tetap', cadangan: 'Cadangan', tamu: 'Tamu' }[type] ?? type
}

function openCell(day, prayer) {
  activeCell.value = { day, prayer }
  const schedule = findSchedule(day, prayer)
  cellForm.value = {
    imam_id: schedule?.imam_id ?? '',
    is_khatib: schedule?.is_khatib ?? false,
    khutbah_theme: schedule?.khutbah_theme ?? '',
  }
  substituteId.value = ''
}

function submitCell() {
  cellFormProcessing.value = true
  router.post(route('admin.prayer.imam-schedule.store'), {
    date: dateFor(activeCell.value.day).format('YYYY-MM-DD'),
    prayer: activeCell.value.prayer,
    ...cellForm.value,
  }, {
    preserveScroll: true,
    onFinish: () => { cellFormProcessing.value = false; activeCell.value = null },
  })
}

function submitSubstitute() {
  if (!existingSchedule.value || !substituteId.value) return
  router.post(route('admin.prayer.imam-schedule.substitute', existingSchedule.value.id), {
    substitute_imam_id: substituteId.value,
  }, { preserveScroll: true, onFinish: () => { activeCell.value = null } })
}

function notifyAll() {
  notifying.value = true
  router.post(route('admin.prayer.imam-schedule.notify'), {}, { preserveScroll: true, onFinish: () => { notifying.value = false } })
}
</script>
