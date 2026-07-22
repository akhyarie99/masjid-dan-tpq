<template>
  <nav class="md:hidden fixed bottom-0 inset-x-0 z-30 h-16 pb-[env(safe-area-inset-bottom)] bg-[var(--bg-surface)] border-t border-[var(--border)] shadow-[0_-2px_8px_rgba(0,0,0,0.06)] flex items-stretch">
    <Link
      v-for="item in mainItems"
      :key="item.label"
      :href="route(item.route)"
      class="flex-1 flex flex-col items-center justify-center gap-0.5 text-[11px] font-medium relative"
      :class="isActive(item.route) ? 'text-primary-600' : 'text-[var(--text-muted)]'"
    >
      <span
        v-if="isActive(item.route)"
        class="absolute top-0 w-8 h-0.5 rounded-full bg-primary-600"
      />
      <span class="w-5 h-5 flex items-center justify-center shrink-0">
        <component :is="item.icon" :size="20" />
      </span>
      {{ item.label }}
    </Link>

    <button
      class="flex-1 flex flex-col items-center justify-center gap-0.5 text-[11px] font-medium text-[var(--text-muted)]"
      @click="showMore = true"
    >
      <span class="w-5 h-5 flex items-center justify-center shrink-0">
        <MoreHorizontal :size="20" />
      </span>
      Lainnya
    </button>

    <TransitionRoot appear :show="showMore" as="template">
      <Dialog as="div" class="relative z-50" @close="showMore = false">
        <TransitionChild as="template" enter="duration-150 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-100 ease-in" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-black/50" />
        </TransitionChild>
        <div class="fixed inset-0 flex items-end">
          <TransitionChild as="template" enter="duration-150 ease-out" enter-from="translate-y-full" enter-to="translate-y-0" leave="duration-100 ease-in" leave-from="translate-y-0" leave-to="translate-y-full">
            <DialogPanel class="w-full bg-[var(--bg-surface)] rounded-t-2xl pb-[calc(1rem+env(safe-area-inset-bottom))]">
              <div class="flex items-center gap-3 px-4 py-3 border-b border-[var(--border)]">
                <div class="w-9 h-9 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold shrink-0 overflow-hidden">
                  <img v-if="masjidLogo" :src="masjidLogo" alt="Logo masjid" class="w-full h-full object-contain" />
                  <span v-else>{{ masjidInitial }}</span>
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-[var(--text-primary)] truncate">SiMasjid</p>
                  <p class="text-xs text-[var(--text-muted)] truncate">{{ masjidName }}</p>
                </div>
              </div>
              <div class="grid grid-cols-4 gap-4 p-4">
                <Link
                  v-for="item in moreItems"
                  :key="item.label"
                  :href="route(item.route)"
                  class="flex flex-col items-center gap-1.5 text-xs text-[var(--text-primary)]"
                  @click="showMore = false"
                >
                  <span class="w-11 h-11 rounded-xl bg-[var(--bg-muted)] flex items-center justify-center">
                    <component :is="item.icon" :size="20" />
                  </span>
                  {{ item.label }}
                </Link>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>
  </nav>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
  LayoutDashboard, Wallet, CalendarDays, GraduationCap, MoreHorizontal,
  Boxes, Clock, Users, Settings, Megaphone,
} from 'lucide-vue-next'

const showMore = ref(false)

const page = usePage()
const masjidName = computed(() => page.props.masjid?.name ?? 'Masjid')
const masjidInitial = computed(() => masjidName.value.charAt(0).toUpperCase())
const masjidLogo = computed(() => page.props.masjid?.logo_url ?? null)

const allItems = [
  { label: 'Dashboard', route: 'admin.dashboard', icon: LayoutDashboard },
  { label: 'Keuangan', route: 'admin.finance.laporan', icon: Wallet },
  { label: 'Kegiatan', route: 'admin.activity.calendar', icon: CalendarDays },
  { label: 'TPQ', route: 'admin.tpq.dashboard', icon: GraduationCap },
  { label: 'Aset', route: 'admin.asset.inventaris.index', icon: Boxes },
  { label: 'Shalat', route: 'admin.prayer.schedule', icon: Clock },
  { label: 'Jamaah', route: 'admin.jamaah.index', icon: Users },
  { label: 'Pengumuman', route: 'pengumuman.index', icon: Megaphone },
  { label: 'Pengaturan', route: 'admin.settings.index', icon: Settings },
]

const availableItems = computed(() => allItems.filter((item) => route().has(item.route)))
const mainItems = computed(() => availableItems.value.slice(0, 4))
const moreItems = computed(() => availableItems.value.slice(4))

function isActive(routeName) {
  return route().current(`${routeName.split('.').slice(0, 2).join('.')}*`)
}
</script>
