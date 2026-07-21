<template>
  <Head title="Kegiatan" />

  <PublicLayout>
    <div class="max-w-3xl mx-auto px-4 py-10 md:py-14">
      <h1 class="text-2xl font-bold text-[var(--text-primary)] mb-1">Kegiatan {{ masjid.name }}</h1>
      <p class="text-sm text-[var(--text-muted)] mb-6">Daftar kegiatan yang akan datang.</p>

      <EmptyState v-if="activities.length === 0" title="Belum ada kegiatan mendatang." />
      <ul v-else class="space-y-3">
        <li v-for="activity in activities" :key="activity.id" class="card p-5">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <AppBadge variant="blue">{{ categoryLabel(activity.category) }}</AppBadge>
              <p class="font-semibold text-[var(--text-primary)] mt-2">{{ activity.name }}</p>
              <p class="text-sm text-[var(--text-muted)] mt-1">{{ formatDate(activity.start_at) }} · {{ activity.location }}</p>
              <p v-if="activity.description" class="text-sm text-[var(--text-muted)] mt-2">{{ activity.description }}</p>
            </div>
            <Link
              v-if="route().has('public.activity.register')"
              :href="route('public.activity.register', activity.id)"
              class="btn-primary shrink-0"
            >
              Daftar
            </Link>
          </div>
        </li>
      </ul>
    </div>
  </PublicLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'

defineProps({
  masjid: { type: Object, required: true },
  activities: { type: Array, default: () => [] },
})

function categoryLabel(category) {
  return {
    kajian_rutin: 'Kajian Rutin', pengajian_akbar: 'Pengajian Akbar', sosial: 'Sosial',
    phbi: 'PHBI', rapat: 'Rapat', lainnya: 'Lainnya',
  }[category] ?? category
}

function formatDate(value) {
  return dayjs(value).format('dddd, DD MMM YYYY, HH:mm')
}
</script>
