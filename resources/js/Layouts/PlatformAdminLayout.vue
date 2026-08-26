<template>
  <div class="min-h-screen bg-[var(--bg-base)]">
    <header class="border-b border-[var(--border)]">
      <div class="flex items-center justify-between p-4 md:p-6 max-w-6xl mx-auto">
        <div class="flex items-center gap-2">
          <div class="w-9 h-9 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold">🛠️</div>
          <span class="font-semibold text-[var(--text-primary)]">Superadmin Platform</span>
        </div>
        <nav class="hidden md:flex items-center gap-1">
          <Link
            :href="route('platform-admin.dashboard')"
            class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-[var(--bg-muted)] transition-colors"
            :class="route().current('platform-admin.dashboard') ? 'text-primary-600' : 'text-[var(--text-primary)]'"
          >
            Tenant
          </Link>
          <Link
            :href="route('platform-admin.revenue')"
            class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-[var(--bg-muted)] transition-colors"
            :class="route().current('platform-admin.revenue') ? 'text-primary-600' : 'text-[var(--text-primary)]'"
          >
            Pendapatan
          </Link>
        </nav>
        <form @submit.prevent="logout">
          <button type="submit" class="btn-secondary">Keluar</button>
        </form>
      </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 md:px-6 py-6 pb-12">
      <AppAlert v-if="$page.props.flash?.success" variant="success" class="mb-4">{{ $page.props.flash.success }}</AppAlert>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppAlert from '@/Components/UI/AppAlert.vue'

function logout() {
  useForm({}).post(route('platform-admin.logout'))
}
</script>
