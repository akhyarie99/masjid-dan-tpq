<template>
  <div>
    <div class="flex items-center justify-between mb-3">
      <p class="text-sm text-[var(--text-muted)]">{{ filledCount }} dari {{ students.length }} santri sudah diisi</p>
    </div>
    <div class="h-2 rounded-full bg-[var(--bg-muted)] overflow-hidden mb-6">
      <div class="h-full bg-primary-600 transition-all" :style="{ width: `${progressPercent}%` }" />
    </div>

    <div class="space-y-3">
      <div v-for="student in local" :key="student.id" class="card p-4">
        <div class="flex items-center gap-2 mb-3">
          <div class="w-9 h-9 rounded-full bg-[var(--bg-muted)] flex items-center justify-center text-sm overflow-hidden shrink-0">
            <img v-if="student.photo" :src="student.photo" class="w-full h-full object-cover" alt="" />
            <span v-else>🧒</span>
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-medium text-[var(--text-primary)] truncate">{{ student.name }}</p>
            <p class="text-xs text-[var(--text-muted)]">{{ student.nis }}</p>
          </div>
          <span v-if="student.filled" class="text-xs font-medium text-green-600 shrink-0">Tersimpan ✓</span>
        </div>

        <div class="grid grid-cols-2 gap-2 mb-3">
          <button
            type="button"
            class="py-2 rounded-lg text-sm font-medium transition-colors"
            :class="student.method === 'iqro' ? 'bg-primary-600 text-white' : 'bg-[var(--bg-muted)] text-[var(--text-muted)]'"
            @click="setMethod(student, 'iqro')"
          >
            Iqro
          </button>
          <button
            type="button"
            class="py-2 rounded-lg text-sm font-medium transition-colors"
            :class="student.method === 'quran' ? 'bg-primary-600 text-white' : 'bg-[var(--bg-muted)] text-[var(--text-muted)]'"
            @click="setMethod(student, 'quran')"
          >
            Al-Qur'an
          </button>
        </div>

        <div v-if="student.method === 'iqro'" class="grid grid-cols-2 gap-2 mb-3">
          <div>
            <label class="block text-xs text-[var(--text-muted)] mb-1">Jilid</label>
            <select v-model.number="student.jilid" class="input !py-1.5" @change="scheduleAutoSave">
              <option v-for="n in 6" :key="n" :value="n">Jilid {{ n }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-[var(--text-muted)] mb-1">Halaman</label>
            <input v-model.number="student.halaman" type="number" min="1" class="input !py-1.5" @input="scheduleAutoSave" />
          </div>
        </div>

        <div v-else class="grid grid-cols-3 gap-2 mb-3">
          <div class="col-span-3 sm:col-span-1">
            <label class="block text-xs text-[var(--text-muted)] mb-1">Surat</label>
            <input v-model="student.surah" type="text" placeholder="Al-Baqarah" class="input !py-1.5" @input="scheduleAutoSave" />
          </div>
          <div>
            <label class="block text-xs text-[var(--text-muted)] mb-1">Ayat Awal</label>
            <input v-model.number="student.ayat_awal" type="number" min="1" class="input !py-1.5" @input="scheduleAutoSave" />
          </div>
          <div>
            <label class="block text-xs text-[var(--text-muted)] mb-1">Ayat Akhir</label>
            <input v-model.number="student.ayat_akhir" type="number" min="1" class="input !py-1.5" @input="scheduleAutoSave" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2 mb-3">
          <button
            v-for="option in keteranganOptions"
            :key="option.value"
            type="button"
            class="py-2 rounded-lg text-sm font-medium transition-colors"
            :class="student.keterangan === option.value ? option.activeClass : 'bg-[var(--bg-muted)] text-[var(--text-muted)]'"
            @click="setKeterangan(student, option.value)"
          >
            {{ option.label }}
          </button>
        </div>

        <input v-model="student.catatan" type="text" placeholder="Catatan (opsional)..." class="input !py-1.5" @input="scheduleAutoSave" />
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

const keteranganOptions = [
  { value: 'lancar', label: 'Lancar', activeClass: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' },
  { value: 'ulang', label: 'Ulang', activeClass: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300' },
]

const filledCount = computed(() => local.value.filter((s) => s.filled).length)
const progressPercent = computed(() => (local.value.length ? (filledCount.value / local.value.length) * 100 : 0))

let saveTimer
function scheduleAutoSave() {
  clearTimeout(saveTimer)
  saveTimer = setTimeout(() => emit('save', local.value), 1000)
}

function setMethod(student, method) {
  student.method = method
  scheduleAutoSave()
}

function setKeterangan(student, keterangan) {
  student.keterangan = keterangan
  scheduleAutoSave()
}

watch(() => props.students, (value) => { local.value = value.map((s) => ({ ...s })) })

defineExpose({ save: () => emit('save', local.value) })
</script>
