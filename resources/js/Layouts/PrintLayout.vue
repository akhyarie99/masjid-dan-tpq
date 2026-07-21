<template>
  <div class="min-h-screen bg-slate-100 dark:bg-slate-900 py-8 print:bg-white print:py-0">
    <div class="max-w-3xl mx-auto flex justify-end gap-2 mb-4 print:hidden">
      <slot name="actions">
        <button class="btn-primary" @click="window.print()">Cetak</button>
      </slot>
    </div>

    <div class="max-w-3xl mx-auto bg-white text-slate-900 shadow-lg print:shadow-none rounded-xl print:rounded-none p-8 md:p-10">
      <slot />
    </div>
  </div>
</template>

<script setup>
//
</script>
