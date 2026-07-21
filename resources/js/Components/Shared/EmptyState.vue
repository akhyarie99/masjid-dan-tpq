<template>
  <div class="flex flex-col items-center justify-center text-center py-12 px-4">
    <component :is="icon" v-if="icon" class="w-10 h-10 text-[var(--text-muted)] mb-3" />
    <p class="text-sm font-medium text-[var(--text-primary)]">{{ title }}</p>
    <p v-if="description" class="text-sm text-[var(--text-muted)] mt-1 max-w-sm">{{ description }}</p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  icon: { type: [Object, Function], default: null },
})
</script>
