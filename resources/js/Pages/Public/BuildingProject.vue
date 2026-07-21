<template>
  <Head :title="project.name" />

  <PublicLayout>
    <div class="max-w-3xl mx-auto px-4 py-10 md:py-14">
      <h1 class="text-2xl font-bold text-[var(--text-primary)] mb-1">{{ project.name }}</h1>
      <p class="text-sm text-[var(--text-muted)] mb-6">{{ project.masjid?.name }}</p>

      <div class="card p-6 mb-6">
        <p class="text-sm text-[var(--text-muted)]">{{ project.description }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
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

        <p class="text-sm text-[var(--text-muted)] mt-4">
          Terkumpul {{ formatCurrency(project.collected_amount) }} dari target {{ formatCurrency(project.target_amount) }}
        </p>

        <Link v-if="route().has('public.donation')" :href="route('public.donation')" class="btn-primary mt-4 w-full justify-center">
          💚 Ikut Berdonasi
        </Link>
      </div>

      <h2 class="text-lg font-semibold text-[var(--text-primary)] mb-3">Perkembangan Proyek</h2>
      <EmptyState v-if="project.updates.length === 0" title="Belum ada update progres." />
      <div v-else class="space-y-4">
        <div v-for="update in project.updates" :key="update.id" class="card p-4">
          <img v-if="update.photo_path" :src="`/storage/${update.photo_path}`" class="w-full h-48 object-cover rounded-lg mb-3" alt="" />
          <p class="font-medium text-[var(--text-primary)]">{{ update.title }}</p>
          <p class="text-xs text-[var(--text-muted)] mt-1">{{ formatDate(update.created_at) }}</p>
          <p v-if="update.description" class="text-sm text-[var(--text-muted)] mt-2">{{ update.description }}</p>
        </div>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

defineProps({
  project: { type: Object, required: true },
})

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
