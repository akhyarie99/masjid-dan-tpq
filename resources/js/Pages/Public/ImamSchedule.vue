<template>
  <Head title="Jadwal Imam" />

  <PublicLayout>
    <div class="max-w-4xl mx-auto px-4 py-10 md:py-14">
      <h1 class="text-2xl font-bold text-[var(--text-primary)] mb-1">Jadwal Imam</h1>
      <p class="text-sm text-[var(--text-muted)] mb-6">{{ masjid.name }} — {{ monthLabel }}</p>

      <EmptyState v-if="schedules.length === 0" title="Jadwal imam bulan ini belum tersedia." />
      <div v-else class="table-responsive card !p-0">
        <table class="table">
          <thead><tr><th>Tanggal</th><th>Shalat</th><th>Imam</th><th>Pengganti</th></tr></thead>
          <tbody>
            <tr v-for="schedule in schedules" :key="schedule.id">
              <td>{{ formatDate(schedule.date) }}</td>
              <td class="capitalize">{{ schedule.prayer }}</td>
              <td>{{ schedule.imam?.name ?? '-' }}</td>
              <td>{{ schedule.is_substituted ? (schedule.substitute_imam?.name ?? '-') : '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

const props = defineProps({
  masjid: { type: Object, required: true },
  month: { type: Number, required: true },
  year: { type: Number, required: true },
  schedules: { type: Array, default: () => [] },
})

const monthLabel = computed(() => dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-01`).format('MMMM YYYY'))

function formatDate(value) {
  return dayjs(value).format('dddd, DD MMM YYYY')
}
</script>
