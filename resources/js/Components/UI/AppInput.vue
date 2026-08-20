<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium text-[var(--text-primary)] mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="relative">
      <input
        :id="id"
        :type="inputType"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        class="input"
        :class="{ 'border-red-500 focus:ring-red-500': error, 'pr-10': isPassword }"
        @input="$emit('update:modelValue', $event.target.value)"
      />
      <button
        v-if="isPassword"
        type="button"
        tabindex="-1"
        class="absolute inset-y-0 right-0 flex items-center px-3 text-[var(--text-muted)] hover:text-[var(--text-primary)]"
        @click="showPassword = !showPassword"
      >
        <EyeOffIcon v-if="showPassword" class="w-4 h-4" />
        <EyeIcon v-else class="w-4 h-4" />
      </button>
    </div>
    <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-xs text-[var(--text-muted)]">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Eye as EyeIcon, EyeOff as EyeOffIcon } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  id: { type: String, default: () => `input-${Math.random().toString(36).slice(2, 9)}` },
})

defineEmits(['update:modelValue'])

const isPassword = computed(() => props.type === 'password')
const showPassword = ref(false)
const inputType = computed(() => (isPassword.value && showPassword.value ? 'text' : props.type))
</script>
