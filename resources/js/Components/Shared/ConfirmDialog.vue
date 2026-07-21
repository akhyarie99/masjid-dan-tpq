<template>
  <AppModal :show="show" :title="title" size="sm" @close="$emit('cancel')">
    <p class="text-sm text-[var(--text-muted)]">{{ message }}</p>
    <template #footer>
      <AppButton variant="secondary" @click="$emit('cancel')">{{ cancelText }}</AppButton>
      <AppButton :variant="danger ? 'danger' : 'primary'" :loading="loading" @click="$emit('confirm')">
        {{ confirmText }}
      </AppButton>
    </template>
  </AppModal>
</template>

<script setup>
import AppModal from '../UI/AppModal.vue'
import AppButton from '../UI/AppButton.vue'

defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, default: 'Apakah Anda yakin?' },
  confirmText: { type: String, default: 'Ya, Lanjutkan' },
  cancelText: { type: String, default: 'Batal' },
  danger: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
})

defineEmits(['confirm', 'cancel'])
</script>
