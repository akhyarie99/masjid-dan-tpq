<template>
  <Head title="Rekap Mengaji Harian" />

  <AdminLayout title="Rekap Mengaji Harian">
    <TpqSubNav />
    <PageHeader title="Rekap Mengaji Harian" description="Riwayat progres mengaji semua santri, bisa diekspor ke Excel.">
      <template #actions>
        <a :href="exportUrl" class="btn-secondary">
          <DownloadIcon class="w-4 h-4" /> Ekspor Excel
        </a>
      </template>
    </PageHeader>

    <AppCard class="mb-6">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <AppSelect
          v-model="filters.class_id"
          label="Kelas"
          :options="[{ label: 'Semua Kelas', value: '' }, ...classes.map((c) => ({ label: c.name, value: c.id }))]"
          @update:model-value="applyFilters"
        />
        <AppSelect
          v-model="filters.month"
          label="Bulan"
          :options="monthOptions"
          @update:model-value="applyFilters"
        />
        <AppSelect
          v-model="filters.year"
          label="Tahun"
          :options="yearOptions"
          @update:model-value="applyFilters"
        />
      </div>
    </AppCard>

    <AppCard>
      <EmptyState v-if="entries.length === 0" title="Belum ada catatan mengaji di periode ini." />
      <div v-else class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Santri</th>
              <th>Kelas</th>
              <th>Progres</th>
              <th>Keterangan</th>
              <th>Catatan</th>
              <th>Dicatat Oleh</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="entry in entries" :key="entry.id">
              <td>{{ formatDate(entry.date) }}</td>
              <td>
                <p class="font-medium text-[var(--text-primary)]">{{ entry.student_name }}</p>
                <p class="text-xs text-[var(--text-muted)]">{{ entry.student_nis }}</p>
              </td>
              <td>{{ entry.class_name ?? '-' }}</td>
              <td>{{ entry.summary }}</td>
              <td>
                <AppBadge :variant="entry.keterangan === 'lancar' ? 'green' : 'yellow'">
                  {{ entry.keterangan === 'lancar' ? 'Lancar' : 'Ulang' }}
                </AppBadge>
              </td>
              <td class="text-sm text-[var(--text-muted)]">{{ entry.catatan ?? '-' }}</td>
              <td class="text-sm text-[var(--text-muted)]">{{ entry.recorded_by ?? '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Download as DownloadIcon } from 'lucide-vue-next'
import dayjs from 'dayjs'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import TpqSubNav from '@/Components/Shared/TpqSubNav.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

const props = defineProps({
  classes: { type: Array, default: () => [] },
  month: { type: Number, required: true },
  year: { type: Number, required: true },
  classId: { type: String, default: null },
  entries: { type: Array, default: () => [] },
})

const filters = reactive({
  class_id: props.classId ?? '',
  month: props.month,
  year: props.year,
})

const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
const monthOptions = monthNames.map((label, index) => ({ label, value: index + 1 }))

const currentYear = new Date().getFullYear()
const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - i).map((y) => ({ label: String(y), value: y }))

function applyFilters() {
  router.get(route('admin.tpq.daily-progress.recap'), {
    class_id: filters.class_id || undefined,
    month: filters.month,
    year: filters.year,
  }, { preserveState: true, preserveScroll: true })
}

const exportUrl = computed(() => {
  const params = new URLSearchParams({ month: filters.month, year: filters.year })
  if (filters.class_id) params.set('class_id', filters.class_id)
  return `${route('admin.tpq.daily-progress.recap.export')}?${params.toString()}`
})

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
