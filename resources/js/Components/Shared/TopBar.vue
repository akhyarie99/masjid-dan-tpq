<template>
  <header class="sticky top-0 z-30 h-16 flex items-center gap-3 px-4 md:px-6 border-b border-[var(--border)] bg-[var(--bg-surface)]/90 backdrop-blur">
    <button class="md:hidden p-2 -ml-2 rounded-lg hover:bg-[var(--bg-muted)]" @click="$emit('toggle-mobile-menu')">
      <MenuIcon class="w-5 h-5" />
    </button>

    <h1 class="flex-1 min-w-0 text-base md:text-lg font-semibold text-[var(--text-primary)] truncate">
      {{ title }}
    </h1>

    <button class="p-2 rounded-lg hover:bg-[var(--bg-muted)] relative">
      <BellIcon class="w-5 h-5 text-[var(--text-muted)]" />
    </button>

    <ThemeToggle />

    <Menu as="div" class="relative">
      <MenuButton class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center text-primary-700 dark:text-primary-300 font-semibold text-xs">
          {{ userInitial }}
        </div>
      </MenuButton>
      <transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-75 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <MenuItems class="absolute right-0 mt-2 w-48 origin-top-right card p-1 shadow-lg">
          <MenuItem v-if="route().has('admin.settings.index')" v-slot="{ active }">
            <Link :href="route('admin.settings.index')" class="block px-3 py-2 rounded-lg text-sm" :class="active ? 'bg-[var(--bg-muted)]' : ''">
              Pengaturan
            </Link>
          </MenuItem>
          <MenuItem v-if="route().has('logout')" v-slot="{ active }">
            <Link :href="route('logout')" method="post" as="button" class="block w-full text-left px-3 py-2 rounded-lg text-sm text-red-600" :class="active ? 'bg-red-50 dark:bg-red-900/20' : ''">
              Keluar
            </Link>
          </MenuItem>
        </MenuItems>
      </transition>
    </Menu>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import { Menu as MenuIcon, Bell as BellIcon } from 'lucide-vue-next'
import ThemeToggle from './ThemeToggle.vue'

defineProps({
  title: { type: String, default: '' },
})

defineEmits(['toggle-mobile-menu'])

const page = usePage()
const userInitial = computed(() => (page.props.auth?.user?.name ?? '?').charAt(0).toUpperCase())
</script>
