<template>
  <Head :title="`Rekap Absensi - ${classData.name}`" />

  <AdminLayout title="Rekap Absensi">
    <PageHeader :title="`Rekap Absensi - ${classData.name}`" :description="monthLabel">
      <template #actions>
        <a :href="exportUrl" class="btn-secondary"><FileTextIcon class="w-4 h-4" /> Export PDF</a>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th class="sticky left-0 bg-[var(--bg-muted)]">Santri</th>
              <th v-for="day in daysInMonth" :key="day" class="text-center !px-2">{{ day }}</th>
              <th class="text-center">Hadir</th>
              <th class="text-center">%</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in recap" :key="row.student.id">
              <td class="sticky left-0 bg-[var(--bg-surface)] whitespace-nowrap">{{ row.student.name }}</td>
              <td v-for="day in daysInMonth" :key="day" class="text-center !px-1">
                <span class="inline-block w-5 h-5 rounded" :class="cellClass(row.days[dateKey(day)])" />
              </td>
              <td class="text-center">{{ row.present_count }}</td>
              <td class="text-center font-medium" :class="row.percent >= 75 ? 'text-green-600' : 'text-red-500'">{{ row.percent }}%</td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <div class="flex items-center gap-4 mt-4 text-xs text-[var(--text-muted)]">
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-500 inline-block" /> Hadir</span>
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-500 inline-block" /> Alfa</span>
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-yellow-400 inline-block" /> Izin/Sakit</span>
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-[var(--bg-muted)] inline-block" /> Libur</span>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { FileText as FileTextIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'

const props = defineProps({
  class: { type: Object, required: true },
  month: { type: Number, required: true },
  year: { type: Number, required: true },
  recap: { type: Array, default: () => [] },
})

const classData = props.class
const monthLabel = computed(() => dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-01`).format('MMMM YYYY'))
const daysInMonth = computed(() => dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-01`).daysInMonth())
const exportUrl = computed(() => route('admin.tpq.attendance.recap.export', { class: classData.id, month: props.month, year: props.year }))

function dateKey(day) {
  return dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-${String(day).padStart(2, '0')}`).format('YYYY-MM-DD')
}

function cellClass(status) {
  return {
    hadir: 'bg-green-500',
    alfa: 'bg-red-500',
    izin: 'bg-yellow-400',
    sakit: 'bg-yellow-400',
  }[status] ?? 'bg-[var(--bg-muted)]'
}
</script>
