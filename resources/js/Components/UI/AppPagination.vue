<template>
  <nav v-if="links.length > 3" class="flex flex-wrap items-center justify-center gap-1">
    <template v-for="(link, index) in links" :key="index">
      <span
        v-if="link.url === null"
        class="px-3 py-1.5 text-sm rounded-lg text-[var(--text-muted)]"
        v-html="link.label"
      />
      <Link
        v-else
        :href="link.url"
        preserve-scroll
        class="px-3 py-1.5 text-sm rounded-lg transition-colors"
        :class="link.active
          ? 'bg-primary-600 text-white'
          : 'text-[var(--text-primary)] hover:bg-[var(--bg-muted)]'"
        v-html="link.label"
      />
    </template>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  links: { type: Array, default: () => [] }, // Laravel paginator ->links() array
})
</script>
