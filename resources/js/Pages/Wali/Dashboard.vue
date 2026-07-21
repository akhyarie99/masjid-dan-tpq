<template>
  <Head title="Dashboard Wali" />

  <WaliLayout>
    <h1 class="text-xl font-bold text-[var(--text-primary)] mb-4">Ananda Saya</h1>

    <EmptyState v-if="students.length === 0" title="Tidak ada data santri terhubung dengan nomor HP ini." />

    <div v-else class="space-y-4">
      <div v-for="student in students" :key="student.id" class="card p-5">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-12 h-12 rounded-full bg-[var(--bg-muted)] flex items-center justify-center text-xl overflow-hidden shrink-0">
            <img v-if="student.photo" :src="student.photo" class="w-full h-full object-cover" alt="" />
            <span v-else>🧒</span>
          </div>
          <div>
            <p class="font-semibold text-[var(--text-primary)]">{{ student.name }}</p>
            <p class="text-xs text-[var(--text-muted)]">NIS {{ student.nis }} · {{ student.class?.name ?? 'Belum ada kelas' }}</p>
          </div>
        </div>

        <p class="text-sm font-medium text-[var(--text-primary)] mb-2">Raport</p>
        <EmptyState v-if="student.reportCards.length === 0" title="Belum ada raport tersedia." />
        <ul v-else class="space-y-2">
          <li v-for="reportCard in student.reportCards" :key="reportCard.id" class="flex items-center justify-between text-sm">
            <span>{{ reportCard.semester?.name }}</span>
            <Link :href="route('wali.reportcard', reportCard.id)" class="text-primary-600 hover:underline">Lihat Raport</Link>
          </li>
        </ul>
      </div>
    </div>
  </WaliLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import WaliLayout from '@/Layouts/WaliLayout.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

defineProps({
  students: { type: Array, default: () => [] },
})
</script>
