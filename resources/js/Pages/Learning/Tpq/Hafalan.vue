<template>
  <Head :title="`Hafalan - ${student.name}`" />

  <AdminLayout title="Progress Hafalan">
    <PageHeader :title="student.name" :description="`NIS: ${student.nis}`" />

    <AppCard>
      <HafalanTracker :progress="progress" :summary="summary" @select="openModal" />
    </AppCard>

    <AppModal :show="selected !== null" :title="selected?.surah_name" @close="selected = null">
      <form v-if="selected" class="space-y-4" @submit.prevent="submit">
        <p class="text-sm text-[var(--text-muted)]">Surah ke-{{ selected.surah_number }} · {{ selected.total_ayah }} ayat</p>
        <AppInput v-model.number="form.memorized_ayah" type="number" label="Jumlah Ayat Dihafal" :min="0" :max="selected.total_ayah" />
        <AppSelect v-model="form.status" label="Status" :options="[{ label: 'Belum', value: 'belum' }, { label: 'Sedang Dihafal', value: 'sedang' }, { label: 'Sudah Hafal', value: 'hafal' }]" />
        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input type="checkbox" :checked="form.status === 'hafal'" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" @change="toggleComplete" />
          Sudah hafal semua ({{ selected.total_ayah }} ayat) — verifikasi
        </label>
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="selected = null">Batal</AppButton>
        <AppButton :loading="saving" @click="submit">Simpan</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import HafalanTracker from '@/Components/Tpq/HafalanTracker.vue'

const props = defineProps({
  student: { type: Object, required: true },
  progress: { type: Array, default: () => [] },
  summary: { type: Object, required: true },
})

const selected = ref(null)
const saving = ref(false)
const form = reactive({ memorized_ayah: 0, status: 'belum' })

function openModal(surah) {
  selected.value = surah
  form.memorized_ayah = surah.memorized_ayah
  form.status = surah.status
}

function toggleComplete(event) {
  if (event.target.checked) {
    form.status = 'hafal'
    form.memorized_ayah = selected.value.total_ayah
  } else {
    form.status = 'sedang'
  }
}

function submit() {
  saving.value = true
  router.post(route('admin.tpq.hafalan.update', props.student.id), {
    surah_number: selected.value.surah_number,
    memorized_ayah: form.memorized_ayah,
    status: form.status,
  }, {
    preserveScroll: true,
    onFinish: () => { saving.value = false; selected.value = null },
  })
}
</script>
