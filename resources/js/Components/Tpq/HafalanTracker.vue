<template>
  <div>
    <div class="grid grid-cols-3 gap-3 mb-6">
      <div class="card p-3 text-center">
        <p class="text-lg font-bold text-green-600">{{ summary.hafal }}</p>
        <p class="text-xs text-[var(--text-muted)]">Surah Hafal</p>
      </div>
      <div class="card p-3 text-center">
        <p class="text-lg font-bold text-yellow-500">{{ summary.sedang }}</p>
        <p class="text-xs text-[var(--text-muted)]">Sedang Dihafal</p>
      </div>
      <div class="card p-3 text-center">
        <p class="text-lg font-bold text-[var(--text-muted)]">{{ summary.belum }}</p>
        <p class="text-xs text-[var(--text-muted)]">Belum Dihafal</p>
      </div>
    </div>

    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
      <button
        v-for="surah in progress"
        :key="surah.surah_number"
        type="button"
        class="rounded-lg p-2.5 text-left transition-colors"
        :class="statusClass(surah.status)"
        @click="$emit('select', surah)"
      >
        <p class="text-[10px] opacity-70">{{ surah.surah_number }}</p>
        <p class="text-xs font-medium truncate">{{ surah.surah_name }}</p>
        <p class="text-[10px] opacity-70">{{ surah.memorized_ayah }}/{{ surah.total_ayah }} ayat</p>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  progress: { type: Array, required: true },
  summary: { type: Object, required: true },
})

defineEmits(['select'])

function statusClass(status) {
  return {
    belum: 'bg-[var(--bg-muted)] text-[var(--text-muted)]',
    sedang: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
    hafal: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
  }[status]
}
</script>
