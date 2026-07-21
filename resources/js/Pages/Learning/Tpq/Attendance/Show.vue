<template>
  <Head :title="`Absensi - ${classData.name}`" />

  <AdminLayout title="Absensi Santri">
    <PageHeader :title="classData.name" :description="formatDate(date)">
      <template #actions>
        <input type="date" :value="date" class="input w-auto" @change="changeDate($event.target.value)" />
        <Link :href="route('admin.tpq.attendance.recap', classData.id)" class="btn-secondary">Rekap</Link>
      </template>
    </PageHeader>

    <AppCard>
      <EmptyState v-if="students.length === 0" title="Belum ada santri di kelas ini untuk tahun ajaran aktif." />
      <AttendanceGrid v-else :students="students" @save="save" />
    </AppCard>

    <Teleport to="body">
      <div v-if="saving || savedAt" class="fixed bottom-4 right-4 z-50">
        <div class="card px-4 py-2 text-sm shadow-lg" :class="saving ? 'text-[var(--text-muted)]' : 'text-green-600'">
          {{ saving ? 'Menyimpan...' : 'Tersimpan ✓' }}
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'
import AttendanceGrid from '@/Components/Tpq/AttendanceGrid.vue'

const props = defineProps({
  class: { type: Object, required: true },
  date: { type: String, required: true },
  students: { type: Array, default: () => [] },
})

const classData = props.class
const saving = ref(false)
const savedAt = ref(false)

function save(students) {
  saving.value = true
  savedAt.value = false

  router.post(route('admin.tpq.attendance.store', classData.id), {
    date: props.date,
    attendances: students.filter((s) => s.status).map((s) => ({ student_id: s.id, status: s.status, notes: s.notes })),
  }, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => {
      saving.value = false
      savedAt.value = true
      setTimeout(() => { savedAt.value = false }, 2000)
    },
  })
}

function changeDate(value) {
  router.get(route('admin.tpq.attendance.show', classData.id), { date: value })
}

function formatDate(value) {
  return dayjs(value).format('dddd, DD MMMM YYYY')
}
</script>
