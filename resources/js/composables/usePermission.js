import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function usePermission() {
  const page = usePage()

  const permissions = computed(() => page.props.auth?.user?.permissions ?? [])
  const roles = computed(() => page.props.auth?.user?.roles ?? [])

  function can(permission) {
    return permissions.value.includes(permission)
  }

  function canAny(list) {
    return list.some((permission) => can(permission))
  }

  function hasRole(role) {
    return roles.value.includes(role)
  }

  return { permissions, roles, can, canAny, hasRole }
}
