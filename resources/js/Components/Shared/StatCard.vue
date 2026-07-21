<template>
  <div class="card p-4 md:p-5">
    <div class="flex items-center justify-between">
      <p class="text-sm text-[var(--text-muted)]">{{ label }}</p>
      <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="iconBg">
        <component :is="icon" class="w-4.5 h-4.5" :class="iconColor" />
      </span>
    </div>
    <p class="mt-2 text-xl md:text-2xl font-bold text-[var(--text-primary)] tabular-nums truncate">
      {{ value }}
    </p>
    <p v-if="trend" class="mt-1 text-xs" :class="trendUp ? 'text-green-600' : 'text-red-500'">
      {{ trend }}
    </p>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  icon: { type: [Object, Function], required: true },
  iconBg: { type: String, default: 'bg-primary-50 dark:bg-primary-900/20' },
  iconColor: { type: String, default: 'text-primary-600 dark:text-primary-400' },
  trend: { type: String, default: '' },
  trendUp: { type: Boolean, default: true },
})
</script>
