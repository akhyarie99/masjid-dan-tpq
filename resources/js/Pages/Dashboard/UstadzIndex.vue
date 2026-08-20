<template>
  <Head title="Dashboard" />

  <AdminLayout title="Dashboard">
    <PageHeader title="Dashboard" description="Ringkasan aktivitas Anda bulan ini." />

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <StatCard label="Hadir Bulan Ini" :value="`${attendanceSummary.presentCount} hari`" :icon="Fingerprint" icon-bg="bg-green-50 dark:bg-green-900/20" icon-color="text-green-600 dark:text-green-400" />
      <StatCard label="Presensi Lengkap" :value="`${attendanceSummary.completeCount} hari`" :icon="CheckCircle2" icon-bg="bg-blue-50 dark:bg-blue-900/20" icon-color="text-blue-600 dark:text-blue-400" />
      <StatCard label="Kelas Diajar" :value="classesTaught.length" :icon="GraduationCap" icon-bg="bg-purple-50 dark:bg-purple-900/20" icon-color="text-purple-600 dark:text-purple-400" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <AppCard title="Input Nilai Harian Terakhir">
        <div v-if="recentProgress.length === 0" class="text-center text-sm text-[var(--text-muted)] py-6">
          Belum ada input mengaji harian.
        </div>
        <ul v-else class="space-y-3">
          <li v-for="entry in recentProgress" :key="entry.id" class="flex items-center justify-between text-sm">
            <div class="min-w-0">
              <p class="font-medium text-[var(--text-primary)] truncate">{{ entry.student?.name }}</p>
              <p class="text-xs text-[var(--text-muted)]">
                {{ entry.class?.name }} — {{ dayjs(entry.date).format('D MMM YYYY') }}
              </p>
            </div>
            <span class="text-xs text-[var(--text-muted)] shrink-0 ml-2">
              {{ entry.method === 'iqro' ? `Iqro ${entry.jilid}` : entry.surah }}
            </span>
          </li>
        </ul>
        <Link v-if="route().has('admin.tpq.daily-progress.index')" :href="route('admin.tpq.daily-progress.index')" class="btn-secondary w-full justify-center mt-4">
          Input Nilai Harian
        </Link>
      </AppCard>

      <AppCard title="Kelas yang Diajar">
        <div v-if="classesTaught.length === 0" class="text-center text-sm text-[var(--text-muted)] py-6">
          Belum ada kelas yang terhubung dengan akun Anda.
        </div>
        <ul v-else class="space-y-2">
          <li v-for="cls in classesTaught" :key="cls.id" class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
            <GraduationCap class="w-4 h-4 text-[var(--text-muted)]" />
            {{ cls.name }}
          </li>
        </ul>
      </AppCard>

      <AppCard title="Pengumuman Terbaru" class="lg:col-span-2">
        <div v-if="announcements.length === 0" class="text-center text-sm text-[var(--text-muted)] py-6">
          Belum ada pengumuman.
        </div>
        <ul v-else class="space-y-3">
          <li v-for="item in announcements" :key="item.id" class="border-b border-[var(--border)] last:border-0 pb-3 last:pb-0">
            <p class="font-medium text-sm text-[var(--text-primary)]">{{ item.title }}</p>
            <p class="text-xs text-[var(--text-muted)] mt-0.5 line-clamp-2">{{ item.content }}</p>
            <p class="text-xs text-[var(--text-muted)] mt-1">{{ dayjs(item.published_at).format('D MMM YYYY') }}</p>
          </li>
        </ul>
      </AppCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Fingerprint, CheckCircle2, GraduationCap } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import StatCard from '@/Components/Shared/StatCard.vue'

defineProps({
  attendanceSummary: { type: Object, required: true },
  recentProgress: { type: Array, default: () => [] },
  classesTaught: { type: Array, default: () => [] },
  announcements: { type: Array, default: () => [] },
})
</script>
