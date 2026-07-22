<template>
  <Head title="TPQ Dashboard" />

  <AdminLayout title="TPQ Dashboard">
    <TpqSubNav />
    <PageHeader title="Dashboard TPQ" description="Ringkasan aktivitas TPQ hari ini." />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard label="Total Santri" :value="stats.totalStudents" :icon="Users" />
      <StatCard label="Hadir Hari Ini" :value="stats.presentToday" :icon="CheckCircle" icon-bg="bg-green-50 dark:bg-green-900/20" icon-color="text-green-600 dark:text-green-400" />
      <StatCard label="SPP Outstanding" :value="formatCurrency(stats.sppOutstanding)" :icon="Wallet" icon-bg="bg-red-50 dark:bg-red-900/20" icon-color="text-red-600 dark:text-red-400" />
      <StatCard label="Raport Pending" :value="stats.reportPending" :icon="FileText" icon-bg="bg-yellow-50 dark:bg-yellow-900/20" icon-color="text-yellow-600 dark:text-yellow-400" />
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <Link v-if="route().has('admin.tpq.attendance.index')" :href="route('admin.tpq.attendance.index')" class="card p-4 text-center hover:bg-[var(--bg-muted)] transition-colors">
        <ClipboardCheck class="w-6 h-6 mx-auto text-primary-600 mb-2" />
        <p class="text-sm font-medium text-[var(--text-primary)]">Absensi Hari Ini</p>
      </Link>
      <Link v-if="route().has('admin.tpq.grade.index')" :href="route('admin.tpq.grade.index')" class="card p-4 text-center hover:bg-[var(--bg-muted)] transition-colors">
        <PenLine class="w-6 h-6 mx-auto text-primary-600 mb-2" />
        <p class="text-sm font-medium text-[var(--text-primary)]">Input Nilai</p>
      </Link>
      <Link v-if="route().has('admin.tpq.report.index')" :href="route('admin.tpq.report.index')" class="card p-4 text-center hover:bg-[var(--bg-muted)] transition-colors">
        <GraduationCap class="w-6 h-6 mx-auto text-primary-600 mb-2" />
        <p class="text-sm font-medium text-[var(--text-primary)]">Kelola Raport</p>
      </Link>
      <Link v-if="route().has('admin.tpq.spp.index')" :href="route('admin.tpq.spp.index')" class="card p-4 text-center hover:bg-[var(--bg-muted)] transition-colors">
        <Wallet class="w-6 h-6 mx-auto text-primary-600 mb-2" />
        <p class="text-sm font-medium text-[var(--text-primary)]">SPP</p>
      </Link>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Users, CheckCircle, Wallet, FileText, ClipboardCheck, PenLine, GraduationCap } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import StatCard from '@/Components/Shared/StatCard.vue'
import TpqSubNav from '@/Components/Shared/TpqSubNav.vue'

defineProps({
  stats: { type: Object, required: true },
})

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}
</script>
