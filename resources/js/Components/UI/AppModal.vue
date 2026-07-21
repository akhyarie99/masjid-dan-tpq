<template>
  <TransitionRoot appear :show="show" as="template">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="duration-150 ease-out" enter-from="opacity-0" enter-to="opacity-100"
        leave="duration-100 ease-in" leave-from="opacity-100" leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/50" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="duration-150 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100"
            leave="duration-100 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full card p-6" :class="sizes[size]">
              <div class="flex items-center justify-between mb-4">
                <DialogTitle class="text-lg font-semibold text-[var(--text-primary)]">
                  {{ title }}
                </DialogTitle>
                <button
                  type="button"
                  class="p-1 rounded-lg hover:bg-[var(--bg-muted)] text-[var(--text-muted)]"
                  @click="$emit('close')"
                >
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>

              <div>
                <slot />
              </div>

              <div v-if="$slots.footer" class="mt-6 flex justify-end gap-2">
                <slot name="footer" />
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { X as XMarkIcon } from 'lucide-vue-next'

defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: '' },
  size: { type: String, default: 'md' }, // sm | md | lg | xl
})

defineEmits(['close'])

const sizes = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
}
</script>
