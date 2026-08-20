<template>
  <Head :title="project.name" />

  <AdminLayout :title="project.name">
    <PageHeader :title="project.name">
      <template #actions>
        <button class="btn-secondary" @click="copyShareLink"><Share2Icon class="w-4 h-4" /> {{ copied ? 'Tersalin!' : 'Bagikan ke Donatur' }}</button>
        <Link v-if="can('wakaf.manage')" :href="route('admin.wakaf.proyek.edit', project.id)" class="btn-secondary">Edit</Link>
        <AppButton v-if="can('wakaf.manage')" @click="showAddUpdate = true"><PlusIcon class="w-4 h-4" /> Tambah Update</AppButton>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <AppCard class="lg:col-span-2">
        <p class="text-sm text-[var(--text-muted)]">{{ project.description }}</p>

        <div class="grid grid-cols-2 gap-4 mt-4">
          <div>
            <div class="flex justify-between text-xs text-[var(--text-muted)] mb-1"><span>Progres Fisik</span><span>{{ project.physical_progress_percent }}%</span></div>
            <div class="h-2.5 rounded-full bg-[var(--bg-muted)] overflow-hidden">
              <div class="h-full bg-primary-600" :style="{ width: `${project.physical_progress_percent}%` }" />
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs text-[var(--text-muted)] mb-1"><span>Progres Dana</span><span>{{ project.funding_percent }}%</span></div>
            <div class="h-2.5 rounded-full bg-[var(--bg-muted)] overflow-hidden">
              <div class="h-full bg-gold-500" :style="{ width: `${Math.min(project.funding_percent, 100)}%` }" />
            </div>
          </div>
        </div>
      </AppCard>

      <AppCard title="RAB & Realisasi">
        <dl class="divide-y divide-[var(--border)] text-sm">
          <div class="py-2 flex justify-between"><dt class="text-[var(--text-muted)]">Target Dana</dt><dd class="font-medium">{{ formatCurrency(project.target_amount) }}</dd></div>
          <div class="py-2 flex justify-between"><dt class="text-[var(--text-muted)]">Dana Terkumpul</dt><dd class="font-medium text-green-600">{{ formatCurrency(project.collected_amount) }}</dd></div>
          <div class="py-2 flex justify-between"><dt class="text-[var(--text-muted)]">Sisa Kebutuhan</dt><dd class="font-medium text-red-500">{{ formatCurrency(Math.max(project.target_amount - project.collected_amount, 0)) }}</dd></div>
        </dl>
      </AppCard>
    </div>

    <AppCard title="Galeri Progres">
      <EmptyState v-if="project.updates.length === 0" title="Belum ada update progres." />
      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="update in project.updates" :key="update.id" class="card p-4">
          <img v-if="update.photo_path" :src="`/storage/${update.photo_path}`" class="w-full h-40 object-cover rounded-lg mb-3" alt="" />
          <p class="font-medium text-[var(--text-primary)]">{{ update.title }}</p>
          <p class="text-xs text-[var(--text-muted)] mt-1">{{ formatDate(update.created_at) }} · {{ update.poster?.name }}</p>
          <p v-if="update.description" class="text-sm text-[var(--text-muted)] mt-2">{{ update.description }}</p>
        </div>
      </div>
    </AppCard>

    <AppModal :show="showAddUpdate" title="Tambah Update Progres" @close="showAddUpdate = false">
      <form class="space-y-4" @submit.prevent="submitUpdate">
        <AppInput v-model="updateForm.title" label="Judul Update" required :error="updateForm.errors.title" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Deskripsi</label>
          <textarea v-model="updateForm.description" rows="3" class="input" />
        </div>
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Foto</label>
          <input type="file" accept="image/*" class="input" @change="updateForm.photo = $event.target.files[0]" />
        </div>
        <AppInput v-model.number="updateForm.progress_percent_at_time" type="number" label="Progres Fisik Saat Ini (%)" :error="updateForm.errors.progress_percent_at_time" />
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showAddUpdate = false">Batal</AppButton>
        <AppButton :loading="updateForm.processing" @click="submitUpdate">Tambah</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Plus as PlusIcon, Share2 as Share2Icon } from 'lucide-vue-next'
import { usePermission } from '@/composables/usePermission'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

const props = defineProps({
  project: { type: Object, required: true },
})

const { can } = usePermission()

const showAddUpdate = ref(false)
const copied = ref(false)
const updateForm = useForm({ title: '', description: '', photo: null, progress_percent_at_time: props.project.physical_progress_percent })

function submitUpdate() {
  updateForm.post(route('admin.wakaf.proyek.update-progress', props.project.id), {
    preserveScroll: true,
    onSuccess: () => { showAddUpdate.value = false; updateForm.reset() },
  })
}

function copyShareLink() {
  const url = route('public.wakaf-proyek', props.project.id)
  navigator.clipboard.writeText(url)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
