<template>
  <button
    @click="toggleTheme"
    class="p-2 rounded-lg bg-[var(--bg-muted)] hover:bg-[var(--border)] transition-colors"
    :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
  >
    <SunIcon v-if="isDark" class="w-5 h-5 text-yellow-400" />
    <MoonIcon v-else class="w-5 h-5 text-slate-600" />
  </button>
</template>

<script setup>
import { Sun as SunIcon, Moon as MoonIcon } from 'lucide-vue-next'
import { useDarkMode } from '@/composables/useDarkMode'
const { isDark, toggleTheme } = useDarkMode()
</script>
