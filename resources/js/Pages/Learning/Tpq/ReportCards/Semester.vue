<template>
  <Head :title="`Raport - ${semester.name}`" />

  <AdminLayout title="Raport TPQ">
    <PageHeader :title="`Raport - ${semester.name}`">
      <template #actions>
        <AppButton variant="secondary" :loading="generatingAll" @click="generateAll"><RefreshCwIcon class="w-4 h-4" /> Generate Semua</AppButton>
        <a :href="route('admin.tpq.report.download-all', semester.id)" class="btn-secondary"><DownloadIcon class="w-4 h-4" /> Download ZIP</a>
        <AppButton :loading="sendingAll" @click="sendAll"><SendIcon class="w-4 h-4" /> Kirim WA Semua</AppButton>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>NIS</th><th>Nama</th><th>Nilai Rata-rata</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <tr v-if="students.length === 0">
              <td colspan="5" class="text-center text-[var(--text-muted)] py-8">Belum ada santri aktif.</td>
            </tr>
            <tr v-for="student in students" :key="student.id">
              <td>{{ student.nis }}</td>
              <td>{{ student.name }}</td>
              <td>{{ student.reportCard?.average_score ?? '-' }}</td>
              <td>
                <AppBadge v-if="student.reportCard?.is_distributed" variant="green">Sudah Dikirim WA</AppBadge>
                <AppBadge v-else-if="student.reportCard" variant="blue">Sudah Dibuat</AppBadge>
                <AppBadge v-else variant="gray">Belum Dibuat</AppBadge>
              </td>
              <td>
                <div class="flex items-center gap-2">
                  <button class="text-primary-600 text-sm hover:underline" @click="generateOne(student)">
                    {{ student.reportCard ? 'Generate Ulang' : 'Generate' }}
                  </button>
                  <a v-if="student.reportCard" :href="route('admin.tpq.report.preview', student.reportCard.id)" class="text-primary-600 text-sm hover:underline">Preview</a>
                  <a v-if="student.reportCard" :href="route('admin.tpq.report.pdf', student.reportCard.id)" class="text-primary-600 text-sm hover:underline">PDF</a>
                  <button v-if="student.reportCard" class="text-primary-600 text-sm hover:underline" @click="sendOne(student.reportCard)">Kirim WA</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { RefreshCw as RefreshCwIcon, Download as DownloadIcon, Send as SendIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  semester: { type: Object, required: true },
  students: { type: Array, default: () => [] },
})

const generatingAll = ref(false)
const sendingAll = ref(false)

function generateOne(student) {
  router.post(route('admin.tpq.report.generate', [props.semester.id, student.id]), {}, { preserveScroll: true })
}

function generateAll() {
  generatingAll.value = true
  router.post(route('admin.tpq.report.generate-all', props.semester.id), {}, { preserveScroll: true, onFinish: () => { generatingAll.value = false } })
}

function sendOne(reportCard) {
  router.post(route('admin.tpq.report.send-wa', reportCard.id), {}, { preserveScroll: true })
}

function sendAll() {
  sendingAll.value = true
  router.post(route('admin.tpq.report.send-wa-all', props.semester.id), {}, { preserveScroll: true, onFinish: () => { sendingAll.value = false } })
}
</script>
