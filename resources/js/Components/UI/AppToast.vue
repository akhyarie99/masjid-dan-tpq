<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2 w-[calc(100%-2rem)] max-w-sm">
      <TransitionGroup
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <AppAlert
          v-for="toast in toasts"
          :key="toast.id"
          :variant="toast.variant"
          dismissible
          class="shadow-lg"
          @dismiss="remove(toast.id)"
        >
          {{ toast.message }}
        </AppAlert>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useFlash } from '@/composables/useFlash'
import AppAlert from './AppAlert.vue'

const { success, error } = useFlash()
const toasts = ref([])
let counter = 0

function push(variant, message) {
  const id = ++counter
  toasts.value.push({ id, variant, message })
  setTimeout(() => remove(id), 5000)
}

function remove(id) {
  toasts.value = toasts.value.filter((toast) => toast.id !== id)
}

watch(success, (message) => {
  if (message) push('success', message)
})

watch(error, (message) => {
  if (message) push('error', message)
})
</script>
