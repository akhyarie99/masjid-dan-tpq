<template>
  <div>
    <!-- Progress -->
    <div class="flex items-center justify-between mb-3">
      <p class="text-sm text-[var(--text-muted)]">{{ filledCount }} dari {{ students.length }} santri sudah diisi</p>
      <button type="button" class="text-sm text-primary-600 hover:underline" @click="markAllPresent">Hadir Semua</button>
    </div>
    <div class="h-2 rounded-full bg-[var(--bg-muted)] overflow-hidden mb-6">
      <div class="h-full bg-primary-600 transition-all" :style="{ width: `${progressPercent}%` }" />
    </div>

    <!-- Desktop table -->
    <div class="hidden md:block table-responsive">
      <table class="table">
        <thead>
          <tr><th class="w-10">No</th><th>Santri</th><th>Status</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
          <tr v-for="(student, index) in local" :key="student.id">
            <td>{{ index + 1 }}</td>
            <td>
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-[var(--bg-muted)] flex items-center justify-center text-xs overflow-hidden shrink-0">
                  <img v-if="student.photo" :src="student.photo" class="w-full h-full object-cover" alt="" />
                  <span v-else>🧒</span>
                </div>
                <div>
                  <p class="font-medium text-[var(--text-primary)]">{{ student.name }}</p>
                  <p class="text-xs text-[var(--text-muted)]">{{ student.nis }}</p>
                </div>
              </div>
            </td>
            <td>
              <div class="flex gap-1">
                <button
                  v-for="option in statusOptions"
                  :key="option.value"
                  type="button"
                  class="w-9 h-9 rounded-lg text-base flex items-center justify-center transition-colors"
                  :class="student.status === option.value ? option.activeClass : 'bg-[var(--bg-muted)]'"
                  :title="option.label"
                  @click="setStatus(student, option.value)"
                >
                  {{ option.emoji }}
                </button>
              </div>
            </td>
            <td>
              <input v-model="student.notes" type="text" class="input !py-1" placeholder="Keterangan..." @input="scheduleAutoSave" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile cards -->
    <div class="md:hidden space-y-3">
      <div v-for="student in local" :key="student.id" class="card p-4">
        <div class="flex items-center gap-2 mb-3">
          <div class="w-9 h-9 rounded-full bg-[var(--bg-muted)] flex items-center justify-center text-sm overflow-hidden shrink-0">
            <img v-if="student.photo" :src="student.photo" class="w-full h-full object-cover" alt="" />
            <span v-else>🧒</span>
          </div>
          <div>
            <p class="font-medium text-[var(--text-primary)]">{{ student.name }}</p>
            <p class="text-xs text-[var(--text-muted)]">{{ student.nis }}</p>
          </div>
        </div>
        <div class="grid grid-cols-4 gap-2">
          <button
            v-for="option in statusOptions"
            :key="option.value"
            type="button"
            class="py-2.5 rounded-lg text-xs font-medium flex flex-col items-center gap-1 transition-colors"
            :class="student.status === option.value ? option.activeClass : 'bg-[var(--bg-muted)] text-[var(--text-muted)]'"
            @click="setStatus(student, option.value)"
          >
            <span class="text-lg">{{ option.emoji }}</span>
            {{ option.label }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  students: { type: Array, required: true },
})

const emit = defineEmits(['save'])

const local = ref(props.students.map((s) => ({ ...s })))

const statusOptions = [
  { value: 'hadir', label: 'Hadir', emoji: '✅', activeClass: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' },
  { value: 'izin', label: 'Izin', emoji: '📋', activeClass: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300' },
  { value: 'sakit', label: 'Sakit', emoji: '🤒', activeClass: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300' },
  { value: 'alfa', label: 'Alfa', emoji: '❌', activeClass: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' },
]

const filledCount = computed(() => local.value.filter((s) => s.status).length)
const progressPercent = computed(() => (local.value.length ? (filledCount.value / local.value.length) * 100 : 0))

let saveTimer
function scheduleAutoSave() {
  clearTimeout(saveTimer)
  saveTimer = setTimeout(() => emit('save', local.value), 1000)
}

function setStatus(student, status) {
  student.status = status
  scheduleAutoSave()
}

function markAllPresent() {
  local.value.forEach((s) => { s.status = 'hadir' })
  scheduleAutoSave()
}

watch(() => props.students, (value) => { local.value = value.map((s) => ({ ...s })) })

defineExpose({ save: () => emit('save', local.value) })
</script>
