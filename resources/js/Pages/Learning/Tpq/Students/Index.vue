<template>
  <Head title="Data Santri" />

  <AdminLayout title="Data Santri">
    <TpqSubNav />
    <PageHeader title="Data Santri" description="Kelola data santri TPQ.">
      <template #actions>
        <a :href="route('admin.tpq.santri.import-template')" class="btn-secondary"><DownloadIcon class="w-4 h-4" /> Template</a>
        <button class="btn-secondary" @click="showImport = true"><UploadIcon class="w-4 h-4" /> Import Excel</button>
        <Link :href="route('admin.tpq.santri.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Tambah Santri</Link>
      </template>
    </PageHeader>

    <AppCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <AppInput v-model="filters.search" placeholder="Cari nama/NIS..." />
        <AppSelect v-model="filters.class_id" placeholder="Semua Kelas" :options="classes.map((c) => ({ label: c.name, value: c.id }))" />
        <AppSelect v-model="filters.status" placeholder="Semua Status" :options="[{ label: 'Aktif', value: 'aktif' }, { label: 'Cuti', value: 'cuti' }, { label: 'Lulus', value: 'lulus' }, { label: 'Keluar', value: 'keluar' }]" />
      </div>
    </AppCard>

    <div v-if="selected.length > 0" class="flex items-center gap-3 mb-3">
      <span class="text-sm text-[var(--text-muted)]">{{ selected.length }} santri terpilih</span>
      <button type="button" class="btn-secondary text-xs !text-red-500" @click="bulkDestroy">
        <Trash2Icon class="w-3.5 h-3.5" /> Hapus Terpilih
      </button>
    </div>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th class="w-8">
                <input type="checkbox" :checked="allOnPageSelected" @change="toggleSelectAll" />
              </th>
              <th>NIS</th><th>Nama</th><th>Kelas</th><th>Jenjang</th><th>Wali</th><th>Status</th><th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="students.data.length === 0">
              <td colspan="8" class="text-center text-[var(--text-muted)] py-8">Belum ada santri.</td>
            </tr>
            <tr v-for="student in students.data" :key="student.id">
              <td><input type="checkbox" :value="student.id" v-model="selected" /></td>
              <td>{{ student.nis }}</td>
              <td>{{ student.name }}</td>
              <td>{{ student.student_classes?.[0]?.class?.name ?? '-' }}</td>
              <td>{{ levelLabel(student) }}</td>
              <td>{{ student.guardian_name ?? '-' }} <span class="text-xs text-[var(--text-muted)]">({{ student.guardian_phone }})</span></td>
              <td><AppBadge :variant="statusVariant(student.status)">{{ statusLabel(student.status) }}</AppBadge></td>
              <td>
                <div class="flex items-center gap-2">
                  <Link :href="route('admin.tpq.santri.edit', student.id)" class="text-primary-600 text-sm hover:underline">Edit</Link>
                  <Link :href="route('admin.tpq.hafalan.show', student.id)" class="text-primary-600 text-sm hover:underline">Hafalan</Link>
                  <a :href="route('admin.tpq.santri.card', student.id)" target="_blank" class="text-primary-600 text-sm hover:underline">Kartu</a>
                  <button type="button" class="text-primary-600 text-sm hover:underline" @click="resetWaliPassword(student)">Reset Password Wali</button>
                  <button type="button" class="text-red-500 text-sm hover:underline" @click="destroyOne(student)">Hapus</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="students.links" />
    </div>

    <AppModal :show="showImport" title="Import Data Santri" @close="showImport = false">
      <form class="space-y-4" @submit.prevent="submitImport">
        <p class="text-sm text-[var(--text-muted)]">
          Unggah file Excel/CSV sesuai template. Kolom <code>nis</code> boleh dikosongkan (dibuat otomatis).
          Kolom <code>kelas</code> harus persis sama dengan nama kelas yang sudah ada di menu Kelas TPQ,
          dan santri hanya masuk kelas kalau ada tahun ajaran yang sedang aktif.
        </p>
        <input type="file" class="input" accept=".csv,.xlsx,.xls" @change="importForm.file = $event.target.files[0]" />
        <p v-if="importForm.errors.file" class="text-xs text-red-500">{{ importForm.errors.file }}</p>
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showImport = false">Batal</AppButton>
        <AppButton :loading="importForm.processing" @click="submitImport">Import</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { Plus as PlusIcon, Download as DownloadIcon, Upload as UploadIcon, Trash2 as Trash2Icon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import TpqSubNav from '@/Components/Shared/TpqSubNav.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  students: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  classes: { type: Array, default: () => [] },
  activeYear: { type: Object, default: null },
})

const filters = reactive({
  search: props.filters.search ?? '',
  class_id: props.filters.class_id ?? '',
  status: props.filters.status ?? '',
})

watch(filters, () => {
  router.get(route('admin.tpq.santri.index'), { ...filters }, { preserveState: true, replace: true })
}, { deep: true })

const selected = ref([])

// Halaman/filter berganti -> daftar santri yang tampil beda, jadi seleksi lama
// (berisi id dari halaman sebelumnya) tidak relevan lagi dan bisa menyesatkan
// kalau tetap dianggap "terpilih".
watch(() => props.students.data, () => { selected.value = [] })

const allOnPageSelected = computed(() =>
  props.students.data.length > 0 && props.students.data.every((s) => selected.value.includes(s.id))
)

function toggleSelectAll() {
  selected.value = allOnPageSelected.value ? [] : props.students.data.map((s) => s.id)
}

function destroyOne(student) {
  if (!confirm(`Hapus data santri ${student.name}? Tindakan ini tidak bisa dibatalkan.`)) return
  router.delete(route('admin.tpq.santri.destroy', student.id), { preserveScroll: true })
}

function bulkDestroy() {
  if (!confirm(`Hapus ${selected.value.length} santri terpilih? Tindakan ini tidak bisa dibatalkan.`)) return
  router.delete(route('admin.tpq.santri.bulk-destroy'), {
    data: { student_ids: selected.value },
    preserveScroll: true,
    onSuccess: () => { selected.value = [] },
  })
}

const showImport = ref(false)
const importForm = useForm({ file: null })

function submitImport() {
  importForm.post(route('admin.tpq.santri.import'), {
    preserveScroll: true,
    onSuccess: () => { showImport.value = false },
  })
}

function levelLabel(student) {
  return student.current_method === 'quran' ? "Al-Qur'an" : `Iqro ${student.current_jilid}`
}

function statusLabel(status) {
  return { aktif: 'Aktif', cuti: 'Cuti', lulus: 'Lulus', keluar: 'Keluar' }[status] ?? status
}

function statusVariant(status) {
  return { aktif: 'green', cuti: 'yellow', lulus: 'blue', keluar: 'gray' }[status] ?? 'gray'
}

function resetWaliPassword(student) {
  if (!confirm(`Reset password wali ${student.name} ke NIS (${student.nis})? Beri tahu password baru ini ke wali santri.`)) return
  router.post(route('admin.tpq.santri.reset-wali-password', student.id), {}, { preserveScroll: true })
}
</script>
