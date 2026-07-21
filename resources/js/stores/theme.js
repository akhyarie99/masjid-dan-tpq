import { defineStore } from 'pinia'
import { useDarkMode } from '@/composables/useDarkMode'

export const useThemeStore = defineStore('theme', () => {
  const { isDark, toggleTheme } = useDarkMode()

  return { isDark, toggleTheme }
})
