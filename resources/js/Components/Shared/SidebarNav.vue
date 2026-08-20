<template>
  <aside :class="mobile ? 'flex flex-col w-full h-full bg-[var(--bg-surface)]' : 'hidden md:flex md:flex-col w-[260px] shrink-0 h-screen sticky top-0 border-r border-[var(--border)] bg-[var(--bg-surface)]'">
    <div class="flex items-center gap-3 px-5 h-16 border-b border-[var(--border)]">
      <div class="w-9 h-9 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold overflow-hidden shrink-0">
        <img v-if="masjidLogo" :src="masjidLogo" alt="Logo masjid" class="w-full h-full object-contain" />
        <span v-else>{{ masjidInitial }}</span>
      </div>
      <div class="min-w-0">
        <p class="text-sm font-semibold text-[var(--text-primary)] truncate">SiMasjid</p>
        <p class="text-xs text-[var(--text-muted)] truncate">{{ masjidName }}</p>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
      <template v-for="item in visibleItems" :key="item.label">
        <Link
          v-if="item.route"
          :href="route(item.route)"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors border-l-[3px]"
          :class="isActive(item)
            ? 'bg-primary-600 text-white border-primary-800'
            : 'text-[var(--text-primary)] border-transparent hover:bg-primary-50 dark:hover:bg-primary-900/20'"
        >
          <component :is="item.icon" class="w-4.5 h-4.5 shrink-0" />
          <span class="truncate">{{ item.label }}</span>
        </Link>
      </template>
    </nav>

    <div class="border-t border-[var(--border)] p-3">
      <Link
        v-if="route().has('profile.show')"
        :href="route('profile.show')"
        class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
      >
        <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center text-primary-700 dark:text-primary-300 font-semibold text-sm shrink-0 overflow-hidden">
          <img v-if="user?.avatar_url" :src="user.avatar_url" alt="Foto profil" class="w-full h-full object-cover" />
          <span v-else>{{ userInitial }}</span>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-[var(--text-primary)] truncate">{{ user?.name }}</p>
          <p class="text-xs text-[var(--text-muted)] truncate">{{ roleLabel }}</p>
        </div>
      </Link>
      <Link
        v-if="route().has('logout')"
        :href="route('logout')"
        method="post"
        as="button"
        class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
      >
        <LogOutIcon class="w-4 h-4" />
        Keluar
      </Link>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  LayoutDashboard, Wallet, Boxes, CalendarDays, Clock, BookOpen,
  GraduationCap, Users, Moon as RamadhanIcon, Building2, FileBarChart,
  Settings, Megaphone, LogOut as LogOutIcon, HandCoins, HandHeart, MessageCircle,
  Library as LibraryIcon, Fingerprint as FingerprintIcon,
} from 'lucide-vue-next'
import { usePermission } from '@/composables/usePermission'

defineProps({
  mobile: { type: Boolean, default: false },
})

const page = usePage()
const { can } = usePermission()

const user = computed(() => page.props.auth?.user)
const masjidName = computed(() => page.props.masjid?.name ?? 'Masjid')
const masjidInitial = computed(() => masjidName.value.charAt(0).toUpperCase())
const masjidLogo = computed(() => page.props.masjid?.logo_url ?? null)
const userInitial = computed(() => (user.value?.name ?? '?').charAt(0).toUpperCase())
const roleLabel = computed(() => user.value?.roles?.[0] ?? '-')

// `match` mendefinisikan pola route mana saja yang membuat item ini aktif.
// Item tanpa `match` eksplisit memakai 2 segmen pertama nama route-nya (perilaku
// lama) — cukup untuk section yang cuma punya satu entri sidebar. Item yang
// namespace route-nya dipakai bareng beberapa entri sidebar lain (mis. semua
// fitur Jamaah ada di bawah "admin.jamaah.*") WAJIB didaftarkan eksplisit di
// sini, supaya tidak ikut menyala saat entri lain di namespace yang sama aktif.
const items = [
  { label: 'Dashboard', route: 'admin.dashboard', icon: LayoutDashboard, permission: null },
  { label: 'Keuangan', route: 'admin.finance.laporan', icon: Wallet, permission: 'finance.view', match: ['admin.finance.*'] },
  { label: 'Aset', route: 'admin.asset.inventaris.index', icon: Boxes, permission: 'asset.view' },
  { label: 'Kegiatan', route: 'admin.activity.calendar', icon: CalendarDays, permission: 'activity.view' },
  { label: 'Shalat & Imam', route: 'admin.prayer.schedule', icon: Clock, permission: 'prayer.view' },
  { label: 'Zakat', route: 'admin.finance.zakat.index', icon: HandCoins, permission: 'finance.view', match: ['admin.finance.zakat.*'] },
  { label: 'Kajian', route: 'admin.study.sesi.index', icon: BookOpen, permission: 'study.view', match: ['admin.study.sesi.*'] },
  { label: 'Majelis Taklim', route: 'admin.study.majelis.index', icon: BookOpen, permission: 'study.view', match: ['admin.study.majelis.*'] },
  { label: 'TPQ', route: 'admin.tpq.dashboard', icon: GraduationCap, permission: 'tpq.view' },
  { label: 'Presensi Staf', route: 'admin.staff-attendance.index', icon: FingerprintIcon, permission: 'staff-attendance.view-own' },
  {
    label: 'Jamaah',
    route: 'admin.jamaah.index',
    icon: Users,
    permission: 'jamaah.view',
    match: [
      'admin.jamaah.index', 'admin.jamaah.create', 'admin.jamaah.store',
      'admin.jamaah.edit', 'admin.jamaah.update', 'admin.jamaah.destroy',
      'admin.jamaah.card', 'admin.jamaah.import', 'admin.jamaah.import-template',
    ],
  },
  { label: 'Program Sosial', route: 'admin.jamaah.program-sosial.index', icon: HandHeart, permission: 'jamaah.view', match: ['admin.jamaah.program-sosial.*'] },
  { label: 'Broadcast WA', route: 'admin.jamaah.broadcast', icon: MessageCircle, permission: 'jamaah.view', match: ['admin.jamaah.broadcast*'] },
  { label: 'Ramadhan', route: 'admin.ramadhan.index', icon: RamadhanIcon, permission: 'ramadhan.view' },
  { label: 'Wakaf', route: 'admin.wakaf.index', icon: Building2, permission: 'wakaf.view' },
  { label: 'Perpustakaan', route: 'admin.library.index', icon: LibraryIcon, permission: 'library.view' },
  { label: 'Laporan', route: 'admin.report.finance', icon: FileBarChart, permission: 'report.view' },
  { label: 'Pengumuman', route: 'pengumuman.index', icon: Megaphone, permission: 'announcement.view' },
  { label: 'Pengaturan', route: 'admin.settings.index', icon: Settings, permission: 'settings.manage' },
]

const visibleItems = computed(() =>
  items.filter((item) => {
    if (!route().has(item.route)) return false
    if (item.permission && !can(item.permission) && !can('settings.manage')) return false
    return true
  })
)

function isActive(item) {
  const patterns = item.match ?? [`${item.route.split('.').slice(0, 2).join('.')}*`]
  return patterns.some((pattern) => route().current(pattern))
}
</script>
