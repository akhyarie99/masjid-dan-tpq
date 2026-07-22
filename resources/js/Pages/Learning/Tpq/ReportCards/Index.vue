<template>
  <Head title="Raport" />

  <AdminLayout title="Raport TPQ">
    <TpqSubNav />
    <PageHeader title="Raport TPQ" description="Pilih semester untuk mengelola raport santri." />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <EmptyState v-if="semesters.length === 0" title="Belum ada semester. Buat semester terlebih dahulu." class="col-span-full" />
      <Link
        v-for="semester in semesters"
        :key="semester.id"
        :href="route('admin.tpq.report.semester', semester.id)"
        class="card p-5 hover:bg-[var(--bg-muted)] transition-colors"
      >
        <p class="font-medium text-[var(--text-primary)]">{{ semester.name }}</p>
        <p class="text-sm text-[var(--text-muted)] mt-1">Kelola raport →</p>
      </Link>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import TpqSubNav from '@/Components/Shared/TpqSubNav.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

defineProps({
  semesters: { type: Array, default: () => [] },
})
</script>
