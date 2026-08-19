<template>
  <div class="min-h-screen flex flex-col">
    <header class="sticky top-0 z-30 h-16 flex items-center gap-3 px-4 md:px-6 border-b border-[var(--border)] bg-[var(--bg-surface)]">
      <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold text-sm">
        🕌
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-[var(--text-primary)] truncate">Portal Wali Murid</p>
      </div>
      <ThemeToggle />
      <Link :href="route('wali.logout')" method="post" as="button" class="btn-secondary !text-xs">
        Keluar
      </Link>
    </header>

    <main class="flex-1 p-4 md:p-6">
      <InstallAppPrompt />
      <slot />
    </main>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ThemeToggle from '@/Components/Shared/ThemeToggle.vue'
import InstallAppPrompt from '@/Components/Wali/InstallAppPrompt.vue'
</script>
