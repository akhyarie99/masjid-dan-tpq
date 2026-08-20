<template>
  <Head title="Proyek Pembangunan" />

  <AdminLayout title="Proyek Pembangunan">
    <PageHeader title="Proyek Pembangunan" description="Pantau progres fisik dan dana proyek pembangunan masjid.">
      <template #actions>
        <Link :href="route('admin.wakaf.index')" class="btn-secondary">Data Wakaf</Link>
        <Link v-if="can('wakaf.manage')" :href="route('admin.wakaf.proyek.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Buat Proyek</Link>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <EmptyState v-if="projects.length === 0" title="Belum ada proyek pembangunan" class="col-span-full" />
      <Link v-for="project in projects" :key="project.id" :href="route('admin.wakaf.proyek.show', project.id)" class="card p-4 hover:bg-[var(--bg-muted)] transition-colors">
        <div class="flex items-start justify-between mb-3">
          <p class="font-medium text-[var(--text-primary)]">{{ project.name }}</p>
          <AppBadge :variant="statusVariant(project.status)">{{ statusLabel(project.status) }}</AppBadge>
        </div>

        <div class="space-y-2">
          <div>
            <div class="flex justify-between text-xs text-[var(--text-muted)] mb-1">
              <span>Progres Fisik</span><span>{{ project.physical_progress_percent }}%</span>
            </div>
            <div class="h-2 rounded-full bg-[var(--bg-muted)] overflow-hidden">
              <div class="h-full bg-primary-600" :style="{ width: `${project.physical_progress_percent}%` }" />
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs text-[var(--text-muted)] mb-1">
              <span>Progres Dana</span><span>{{ project.funding_percent }}%</span>
            </div>
            <div class="h-2 rounded-full bg-[var(--bg-muted)] overflow-hidden">
              <div class="h-full bg-gold-500" :style="{ width: `${Math.min(project.funding_percent, 100)}%` }" />
            </div>
          </div>
        </div>

        <p class="text-xs text-[var(--text-muted)] mt-3">
          {{ formatCurrency(project.collected_amount) }} / {{ formatCurrency(project.target_amount) }}
        </p>
      </Link>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Plus as PlusIcon } from 'lucide-vue-next'
import { usePermission } from '@/composables/usePermission'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

defineProps({
  projects: { type: Array, default: () => [] },
})

const { can } = usePermission()

function statusLabel(status) {
  return { planning: 'Perencanaan', ongoing: 'Berjalan', completed: 'Selesai' }[status] ?? status
}

function statusVariant(status) {
  return { planning: 'gray', ongoing: 'blue', completed: 'green' }[status] ?? 'gray'
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}
</script>
