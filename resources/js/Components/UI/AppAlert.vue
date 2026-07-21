<template>
  <div class="flex items-start gap-3 rounded-xl border p-4 text-sm" :class="styles[variant]">
    <component :is="icons[variant]" class="w-5 h-5 shrink-0 mt-0.5" />
    <div class="flex-1">
      <p v-if="title" class="font-medium">{{ title }}</p>
      <p><slot /></p>
    </div>
    <button v-if="dismissible" type="button" class="shrink-0 opacity-70 hover:opacity-100" @click="$emit('dismiss')">
      <XMarkIcon class="w-4 h-4" />
    </button>
  </div>
</template>

<script setup>
import { CheckCircle, AlertTriangle, XCircle, Info, X as XMarkIcon } from 'lucide-vue-next'

defineProps({
  variant: { type: String, default: 'info' }, // success | error | warning | info
  title: { type: String, default: '' },
  dismissible: { type: Boolean, default: false },
})

defineEmits(['dismiss'])

const styles = {
  success: 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200',
  error: 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200',
  warning: 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-200',
  info: 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200',
}

const icons = {
  success: CheckCircle,
  error: XCircle,
  warning: AlertTriangle,
  info: Info,
}
</script>
