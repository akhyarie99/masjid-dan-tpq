import { computed } from 'vue'
import { defineStore } from 'pinia'
import { usePage } from '@inertiajs/vue3'

export const useAuthStore = defineStore('auth', () => {
  const page = usePage()

  const user = computed(() => page.props.auth?.user ?? null)
  const masjid = computed(() => page.props.masjid ?? null)
  const isAuthenticated = computed(() => user.value !== null)

  function hasRole(role) {
    return user.value?.roles?.includes(role) ?? false
  }

  function can(permission) {
    return user.value?.permissions?.includes(permission) ?? false
  }

  return { user, masjid, isAuthenticated, hasRole, can }
})
