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
import { usePermission } from '@/composables/usePermission'

const { can } = usePermission()

// Tahun Ajaran/Semester/Kelas/SPP/Sertifikat/Pengaturan sengaja digerbang
// tpq.manage (bukan tpq.view) — ini area konfigurasi TPQ, ustadz tidak
// perlu (dan tidak boleh) melihatnya sama sekali, cukup "Harian".
const items = [
  { label: 'Dashboard', route: 'admin.tpq.dashboard', permission: 'tpq.view' },
  { label: 'Tahun Ajaran', route: 'admin.tpq.tahun-ajaran.index', permission: 'tpq.manage' },
  { label: 'Semester', route: 'admin.tpq.semester.index', permission: 'tpq.manage' },
  { label: 'Kelas', route: 'admin.tpq.kelas.index', permission: 'tpq.manage' },
  { label: 'Data Santri', route: 'admin.tpq.santri.index', permission: 'tpq.view' },
  { label: 'Absensi', route: 'admin.tpq.attendance.index', permission: 'tpq.view' },
  { label: 'Harian', route: 'admin.tpq.daily-progress.index', permission: 'tpq.daily-progress.view' },
  { label: 'Nilai', route: 'admin.tpq.grade.index', permission: 'tpq.grade' },
  { label: 'Raport', route: 'admin.tpq.report.index', permission: 'tpq.report' },
  { label: 'SPP', route: 'admin.tpq.spp.index', permission: 'tpq.manage' },
  { label: 'Sertifikat', route: 'admin.tpq.sertifikat.index', permission: 'tpq.manage' },
  { label: 'Pengaturan', route: 'admin.tpq.pengaturan.edit', permission: 'tpq.manage' },
]

const visibleItems = computed(() =>
  items.filter((item) => route().has(item.route) && can(item.permission))
)

function isActive(routeName) {
  return route().current(`${routeName.split('.').slice(0, 3).join('.')}*`)
}
</script>
