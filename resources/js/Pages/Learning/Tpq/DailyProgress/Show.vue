<template>
  <Head :title="`Mengaji Harian - ${classData.name}`" />

  <AdminLayout title="Mengaji Harian">
    <PageHeader :title="classData.name" :description="formatDate(date)">
      <template #actions>
        <input type="date" :value="date" class="input w-auto" @change="changeDate($event.target.value)" />
      </template>
    </PageHeader>

    <AppCard>
      <EmptyState v-if="students.length === 0" title="Belum ada santri di kelas ini untuk tahun ajaran aktif." />
      <DailyProgressGrid v-else :students="students" @save="save" />
    </AppCard>

    <Teleport to="body">
      <div v-if="saving || savedAt" class="fixed bottom-4 right-4 z-50">
        <div class="card px-4 py-2 text-sm shadow-lg" :class="saving ? 'text-[var(--text-muted)]' : 'text-green-600'">
          {{ saving ? 'Menyimpan...' : 'Tersimpan, orang tua diberi tahu ✓' }}
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'
import DailyProgressGrid from '@/Components/Tpq/DailyProgressGrid.vue'

const props = defineProps({
  class: { type: Object, required: true },
  date: { type: String, required: true },
  students: { type: Array, default: () => [] },
})

const classData = props.class
const saving = ref(false)
const savedAt = ref(false)

function save(students) {
  const entries = students
    .filter((s) => (s.method === 'iqro' ? s.jilid : s.surah))
    .map((s) => ({
      student_id: s.id,
      method: s.method,
      jilid: s.jilid,
      halaman: s.halaman,
      surah: s.surah,
      ayat_awal: s.ayat_awal,
      ayat_akhir: s.ayat_akhir,
      keterangan: s.keterangan,
      catatan: s.catatan,
    }))

  if (entries.length === 0) return

  saving.value = true
  savedAt.value = false

  router.post(route('admin.tpq.daily-progress.store', classData.id), {
    date: props.date,
    entries,
  }, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => {
      saving.value = false
      savedAt.value = true
      setTimeout(() => { savedAt.value = false }, 2500)
    },
  })
}

function changeDate(value) {
  router.get(route('admin.tpq.daily-progress.show', classData.id), { date: value })
}

function formatDate(value) {
  return dayjs(value).format('dddd, DD MMMM YYYY')
}
</script>
