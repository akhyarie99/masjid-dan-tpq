<template>
  <Head title="Kelas TPQ" />

  <AdminLayout title="Kelas TPQ">
    <TpqSubNav />
    <PageHeader title="Kelas TPQ" description="Kelola kelas/jenjang TPQ.">
      <template #actions>
        <AppButton @click="openCreate"><PlusIcon class="w-4 h-4" /> Tambah Kelas</AppButton>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-if="classes.length === 0" class="col-span-full">
        <EmptyState title="Belum ada kelas" />
      </div>
      <div v-for="kelas in classes" :key="kelas.id" class="card p-4">
        <div class="flex items-start justify-between">
          <div>
            <p class="font-medium text-[var(--text-primary)]">{{ kelas.name }}</p>
            <p class="text-xs text-[var(--text-muted)]">{{ kelas.student_classes_count }} / {{ kelas.capacity }} santri</p>
          </div>
          <AppBadge :variant="kelas.is_active ? 'green' : 'gray'">{{ kelas.is_active ? 'Aktif' : 'Nonaktif' }}</AppBadge>
        </div>
        <p v-if="kelas.room" class="text-xs text-[var(--text-muted)] mt-2">📍 {{ kelas.room }}</p>
        <p class="text-xs text-[var(--text-muted)] mt-1">
          👤 {{ kelas.teachers?.length ? kelas.teachers.map((t) => t.teacher?.name).join(', ') : 'Belum ada guru pengampu' }}
        </p>
        <button class="text-primary-600 text-sm hover:underline mt-3" @click="openEdit(kelas)">Edit</button>
      </div>
    </div>

    <AppModal :show="showModal" :title="editing ? 'Edit Kelas' : 'Tambah Kelas'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Kelas" required :error="form.errors.name" />
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model.number="form.order" type="number" label="Urutan" :error="form.errors.order" />
          <AppInput v-model.number="form.capacity" type="number" label="Kapasitas" required :error="form.errors.capacity" />
        </div>
        <AppInput v-model="form.room" label="Ruang" :error="form.errors.room" />
        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input v-model="form.is_active" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
          Aktif
        </label>

        <div>
          <p class="block text-sm font-medium text-[var(--text-primary)] mb-1">Ustadz Pengampu</p>
          <p v-if="!activeAcademicYear" class="text-xs text-amber-600 mb-2">
            Belum ada tahun ajaran aktif — aktifkan dulu di menu Tahun Ajaran sebelum menetapkan guru pengampu.
          </p>
          <p v-else-if="teachers.length === 0" class="text-xs text-[var(--text-muted)] mb-2">
            Belum ada user dengan role ustadz. Tambahkan lewat menu Pengguna.
          </p>
          <div v-else class="space-y-2 max-h-40 overflow-y-auto border border-[var(--border)] rounded-lg p-3">
            <label v-for="teacher in teachers" :key="teacher.id" class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
              <input
                type="checkbox"
                :value="teacher.id"
                v-model="form.teacher_ids"
                :disabled="!activeAcademicYear"
                class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500"
              />
              {{ teacher.name }}
            </label>
          </div>
          <p v-if="form.errors.teacher_ids" class="mt-1 text-xs text-red-500">{{ form.errors.teacher_ids }}</p>
        </div>
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showModal = false">Batal</AppButton>
        <AppButton :loading="form.processing" @click="submit">Simpan</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Plus as PlusIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import TpqSubNav from '@/Components/Shared/TpqSubNav.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

defineProps({
  classes: { type: Array, default: () => [] },
  teachers: { type: Array, default: () => [] },
})

const showModal = ref(false)
const editing = ref(null)
const form = useForm({ name: '', order: 0, capacity: 20, room: '', is_active: true })

function openCreate() {
  editing.value = null
  form.reset()
  showModal.value = true
}

function openEdit(kelas) {
  editing.value = kelas
  form.name = kelas.name
  form.order = kelas.order
  form.capacity = kelas.capacity
  form.room = kelas.room
  form.is_active = kelas.is_active
  showModal.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.tpq.kelas.update', editing.value.id), options)
  } else {
    form.post(route('admin.tpq.kelas.store'), options)
  }
}
</script>
