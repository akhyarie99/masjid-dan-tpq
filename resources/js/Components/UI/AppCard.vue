<template>
  <div class="card" :class="padded ? 'p-4 md:p-6' : ''">
    <div v-if="title || $slots.actions" class="flex items-center justify-between mb-4">
      <div>
        <h3 v-if="title" class="text-base font-semibold text-[var(--text-primary)]">{{ title }}</h3>
        <p v-if="subtitle" class="text-sm text-[var(--text-muted)]">{{ subtitle }}</p>
      </div>
      <div v-if="$slots.actions" class="flex items-center gap-2">
        <slot name="actions" />
      </div>
    </div>
    <slot />
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  padded: { type: Boolean, default: true },
})
</script>
