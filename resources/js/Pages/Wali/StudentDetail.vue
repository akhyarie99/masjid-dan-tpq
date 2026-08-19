<template>
  <Head :title="student.name" />

  <WaliLayout>
    <Link :href="route('wali.dashboard')" class="inline-flex items-center gap-1 text-sm text-[var(--text-muted)] hover:text-[var(--text-primary)] mb-4">
      <ArrowLeftIcon class="w-4 h-4" /> Kembali
    </Link>

    <div class="card p-5 mb-4">
      <div class="flex items-center gap-3">
        <div class="w-14 h-14 rounded-full bg-[var(--bg-muted)] flex items-center justify-center text-2xl overflow-hidden shrink-0">
          <img v-if="student.photo" :src="student.photo" class="w-full h-full object-cover" alt="" />
          <span v-else>🧒</span>
        </div>
        <div>
          <p class="text-lg font-bold text-[var(--text-primary)]">{{ student.name }}</p>
          <p class="text-sm text-[var(--text-muted)]">NIS {{ student.nis }}</p>
        </div>
      </div>
    </div>

    <div class="card p-5 mb-4">
      <p class="text-sm font-medium text-[var(--text-primary)] mb-2">Progres Mengaji Terbaru</p>
      <EmptyState v-if="dailyProgress.length === 0" title="Belum ada catatan mengaji." />
      <ul v-else class="space-y-2">
        <li v-for="(entry, index) in dailyProgress" :key="index" class="text-sm border-b border-[var(--border)] last:border-0 pb-2 last:pb-0">
          <div class="flex items-center justify-between">
            <span class="text-[var(--text-primary)] font-medium">{{ formatDate(entry.date) }}</span>
            <span
              class="text-xs font-medium px-2 py-0.5 rounded-full"
              :class="entry.keterangan === 'lancar' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300'"
            >
              {{ entry.keterangan === 'lancar' ? 'Lancar' : 'Ulang' }}
            </span>
          </div>
          <p class="text-[var(--text-muted)] mt-0.5">{{ entry.summary }}</p>
          <p v-if="entry.catatan" class="text-xs text-[var(--text-muted)] italic mt-0.5">"{{ entry.catatan }}"</p>
        </li>
      </ul>
    </div>

    <div class="card p-5">
      <p class="text-sm font-medium text-[var(--text-primary)] mb-2">Raport</p>
      <EmptyState v-if="reportCards.length === 0" title="Belum ada raport tersedia." />
      <ul v-else class="space-y-2">
        <li v-for="reportCard in reportCards" :key="reportCard.id" class="flex items-center justify-between text-sm">
          <span>{{ reportCard.semester?.name }}</span>
          <Link :href="route('wali.reportcard', reportCard.id)" class="text-primary-600 hover:underline">Lihat Raport</Link>
        </li>
      </ul>
    </div>
  </WaliLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft as ArrowLeftIcon } from 'lucide-vue-next'
import dayjs from 'dayjs'
import WaliLayout from '@/Layouts/WaliLayout.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

defineProps({
  student: { type: Object, required: true },
  dailyProgress: { type: Array, default: () => [] },
  reportCards: { type: Array, default: () => [] },
})

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY')
}
</script>
