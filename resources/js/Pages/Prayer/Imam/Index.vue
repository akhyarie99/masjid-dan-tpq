<template>
  <Head title="Data Imam" />

  <AdminLayout title="Data Imam">
    <PageHeader title="Data Imam" description="Kelola daftar imam tetap, cadangan, dan tamu.">
      <template #actions>
        <Link :href="route('admin.prayer.imam.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Tambah Imam</Link>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-if="imams.length === 0" class="col-span-full">
        <EmptyState title="Belum ada data imam" />
      </div>
      <div v-for="imam in imams" :key="imam.id" class="card p-4">
        <div class="flex items-start justify-between">
          <div>
            <p class="font-medium text-[var(--text-primary)]">{{ imam.name }}</p>
            <p class="text-xs text-[var(--text-muted)]">{{ imam.phone }}</p>
          </div>
          <AppBadge :variant="typeVariant(imam.type)">{{ typeLabel(imam.type) }}</AppBadge>
        </div>
        <p v-if="imam.bio" class="text-sm text-[var(--text-muted)] mt-2 line-clamp-2">{{ imam.bio }}</p>
        <Link :href="route('admin.prayer.imam.edit', imam.id)" class="text-primary-600 text-sm hover:underline mt-3 inline-block">Edit</Link>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Plus as PlusIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

defineProps({
  imams: { type: Array, default: () => [] },
})

function typeLabel(type) {
  return { tetap: 'Tetap', cadangan: 'Cadangan', tamu: 'Tamu' }[type] ?? type
}

function typeVariant(type) {
  return { tetap: 'green', cadangan: 'blue', tamu: 'yellow' }[type] ?? 'gray'
}
</script>
