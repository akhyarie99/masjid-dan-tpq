<template>
  <Head title="Tracker Khatam Quran" />

  <AdminLayout title="Tracker Khatam Quran">
    <PageHeader title="Tracker Khatam Al-Quran" :description="`${summary.completed} dari ${summary.total} peserta sudah khatam — ${year}`">
      <template v-if="can('ramadhan.manage')" #actions>
        <AppButton @click="showAdd = true"><PlusIcon class="w-4 h-4" /> Tambah Peserta</AppButton>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <EmptyState v-if="trackers.length === 0" title="Belum ada peserta khatam" class="col-span-full" />
      <div v-for="tracker in trackers" :key="tracker.id" class="card p-4">
        <div class="flex items-start justify-between">
          <p class="font-medium text-[var(--text-primary)]">{{ tracker.participant_name }}</p>
          <AppBadge :variant="tracker.is_completed ? 'green' : 'blue'">{{ tracker.is_completed ? 'Khatam ✓' : `${(tracker.completed_juz ?? []).length}/30 Juz` }}</AppBadge>
        </div>
        <div class="grid grid-cols-10 gap-1 mt-3">
          <button
            v-for="juz in 30"
            :key="juz"
            type="button"
            class="h-5 rounded text-[9px] flex items-center justify-center"
            :class="(tracker.completed_juz ?? []).includes(juz) ? 'bg-primary-600 text-white' : 'bg-[var(--bg-muted)] text-[var(--text-muted)]'"
            @click="toggleJuz(tracker, juz)"
          >
            {{ juz }}
          </button>
        </div>
      </div>
    </div>

    <AppModal :show="showAdd" title="Tambah Peserta Khatam" @close="showAdd = false">
      <form class="space-y-4" @submit.prevent="submitAdd">
        <AppInput v-model="addForm.participant_name" label="Nama Peserta" required :error="addForm.errors.participant_name" />
        <AppInput v-model="addForm.phone" label="No. HP" :error="addForm.errors.phone" />
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showAdd = false">Batal</AppButton>
        <AppButton :loading="addForm.processing" @click="submitAdd">Tambah</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Plus as PlusIcon } from 'lucide-vue-next'
import { usePermission } from '@/composables/usePermission'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

const props = defineProps({
  year: { type: Number, required: true },
  trackers: { type: Array, default: () => [] },
  summary: { type: Object, required: true },
})

const { can } = usePermission()

const showAdd = ref(false)
const addForm = useForm({ participant_name: '', phone: '' })

function submitAdd() {
  addForm.post(route('admin.ramadhan.khatam.store', { year: props.year }), {
    preserveScroll: true,
    onSuccess: () => { showAdd.value = false; addForm.reset() },
  })
}

function toggleJuz(tracker, juz) {
  const current = tracker.completed_juz ?? []
  const next = current.includes(juz) ? current.filter((j) => j !== juz) : [...current, juz].sort((a, b) => a - b)

  router.put(route('admin.ramadhan.khatam.update', tracker.id), { completed_juz: next }, { preserveScroll: true })
}
</script>
