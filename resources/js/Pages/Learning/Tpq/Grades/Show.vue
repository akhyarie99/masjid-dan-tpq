<template>
  <Head :title="`Nilai - ${classData.name}`" />

  <AdminLayout title="Input Nilai">
    <PageHeader :title="classData.name" :description="`Semester: ${semesterData.name}`">
      <template #actions>
        <AppButton :loading="saving" @click="submit">Simpan Semua Nilai</AppButton>
      </template>
    </PageHeader>

    <EmptyState v-if="students.length === 0" title="Belum ada santri di kelas ini." />

    <template v-else>
      <!-- Desktop: tab per mapel -->
      <div class="hidden md:block">
        <div class="flex gap-1 mb-4 overflow-x-auto">
          <button
            v-for="subject in subjects"
            :key="subject.id"
            type="button"
            class="px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap"
            :class="activeSubject === subject.id ? 'bg-primary-600 text-white' : 'bg-[var(--bg-muted)] text-[var(--text-primary)]'"
            @click="activeSubject = subject.id"
          >
            {{ subject.name }}
          </button>
        </div>

        <AppCard :padded="false">
          <div class="table-responsive">
            <table class="table">
              <thead><tr><th>Santri</th><th class="w-32">Nilai</th><th>Deskripsi</th><th class="w-24">Rata-rata</th></tr></thead>
              <tbody>
                <tr v-for="student in students" :key="student.id">
                  <td>{{ student.name }}</td>
                  <td>
                    <input
                      v-model.number="matrix[student.id][activeSubject].score"
                      type="number" min="0" max="100"
                      class="input !py-1.5"
                      :class="scoreClass(matrix[student.id][activeSubject].score)"
                    />
                  </td>
                  <td>
                    <input v-model="matrix[student.id][activeSubject].description" type="text" class="input !py-1.5" placeholder="Catatan (opsional)" />
                  </td>
                  <td class="font-medium">{{ average(student.id) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </AppCard>
      </div>

      <!-- Mobile: accordion per santri -->
      <div class="md:hidden space-y-3">
        <Disclosure v-for="student in students" :key="student.id" v-slot="{ open }">
          <DisclosureButton class="card w-full p-4 flex items-center justify-between">
            <span class="font-medium text-[var(--text-primary)]">{{ student.name }}</span>
            <span class="text-sm text-[var(--text-muted)]">Rata-rata: {{ average(student.id) }}</span>
          </DisclosureButton>
          <DisclosurePanel class="card p-4 mt-1 space-y-3">
            <div v-for="subject in subjects" :key="subject.id">
              <label class="block text-xs font-medium text-[var(--text-muted)] mb-1">{{ subject.name }}</label>
              <input
                v-model.number="matrix[student.id][subject.id].score"
                type="number" min="0" max="100"
                class="input"
                :class="scoreClass(matrix[student.id][subject.id].score)"
              />
            </div>
          </DisclosurePanel>
        </Disclosure>
      </div>
    </template>
  </AdminLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

const props = defineProps({
  class: { type: Object, required: true },
  semester: { type: Object, required: true },
  students: { type: Array, default: () => [] },
  subjects: { type: Array, default: () => [] },
  grades: { type: Array, default: () => [] },
})

const classData = props.class
const semesterData = props.semester
const saving = ref(false)
const activeSubject = ref(props.subjects[0]?.id ?? null)
const kkm = 70

const matrix = reactive({})
props.students.forEach((student) => {
  matrix[student.id] = {}
  props.subjects.forEach((subject) => {
    const existing = props.grades.find((g) => g.student_id === student.id && g.subject_id === subject.id)
    matrix[student.id][subject.id] = {
      score: existing?.score ?? null,
      description: existing?.description ?? '',
    }
  })
})

function average(studentId) {
  const scores = Object.values(matrix[studentId]).map((g) => g.score).filter((s) => s !== null && s !== '')
  if (scores.length === 0) return '-'
  return (scores.reduce((a, b) => a + Number(b), 0) / scores.length).toFixed(1)
}

function scoreClass(score) {
  if (score === null || score === '') return ''
  return Number(score) >= kkm ? '!border-green-400' : '!border-red-400'
}

function submit() {
  saving.value = true
  const grades = []
  for (const studentId in matrix) {
    for (const subjectId in matrix[studentId]) {
      const entry = matrix[studentId][subjectId]
      grades.push({ student_id: studentId, subject_id: subjectId, score: entry.score, description: entry.description })
    }
  }

  router.post(route('admin.tpq.grade.store', [classData.id, semesterData.id]), { grades }, {
    preserveScroll: true,
    onFinish: () => { saving.value = false },
  })
}
</script>
