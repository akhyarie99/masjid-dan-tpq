<template>
  <nav class="flex items-center gap-1 overflow-x-auto pb-3 mb-6 border-b border-[var(--border)] -mt-2">
    <Link
      v-for="item in visibleItems"
      :key="item.route"
      :href="route(item.route)"
      class="shrink-0 px-3 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap"
      :class="isActive(item.route)
        ? 'bg-primary-600 text-white'
        : 'text-[var(--text-muted)] hover:bg-[var(--bg-muted)] hover:text-[var(--text-primary)]'"
    >
      {{ item.label }}
    </Link>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const items = [
  { label: 'Dashboard', route: 'admin.tpq.dashboard' },
  { label: 'Tahun Ajaran', route: 'admin.tpq.tahun-ajaran.index' },
  { label: 'Semester', route: 'admin.tpq.semester.index' },
  { label: 'Kelas', route: 'admin.tpq.kelas.index' },
  { label: 'Data Santri', route: 'admin.tpq.santri.index' },
  { label: 'Absensi', route: 'admin.tpq.attendance.index' },
  { label: 'Nilai', route: 'admin.tpq.grade.index' },
  { label: 'Raport', route: 'admin.tpq.report.index' },
  { label: 'SPP', route: 'admin.tpq.spp.index' },
  { label: 'Sertifikat', route: 'admin.tpq.sertifikat.index' },
  { label: 'Pengaturan', route: 'admin.tpq.pengaturan.edit' },
]

const visibleItems = computed(() => items.filter((item) => route().has(item.route)))

function isActive(routeName) {
  return route().current(`${routeName.split('.').slice(0, 3).join('.')}*`)
}
</script>
