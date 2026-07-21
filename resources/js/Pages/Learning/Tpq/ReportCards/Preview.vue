<template>
  <Head :title="`Preview Raport - ${reportCard.student.name}`" />

  <AdminLayout title="Preview Raport">
    <PageHeader :title="`Raport ${reportCard.student.name}`" :description="`${reportCard.class.name} · ${reportCard.semester.name}`">
      <template #actions>
        <a :href="route('admin.tpq.report.pdf', reportCard.id)" class="btn-primary"><FileTextIcon class="w-4 h-4" /> Download PDF</a>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <AppCard class="lg:col-span-1">
        <dl class="divide-y divide-[var(--border)] text-sm">
          <div class="py-2 flex justify-between"><dt class="text-[var(--text-muted)]">NIS</dt><dd>{{ reportCard.student.nis }}</dd></div>
          <div class="py-2 flex justify-between"><dt class="text-[var(--text-muted)]">Rata-rata</dt><dd class="font-semibold">{{ reportCard.average_score }}</dd></div>
          <div class="py-2 flex justify-between"><dt class="text-[var(--text-muted)]">Hadir</dt><dd>{{ reportCard.present_count }} hari</dd></div>
          <div class="py-2 flex justify-between"><dt class="text-[var(--text-muted)]">Sakit</dt><dd>{{ reportCard.sick_count }} hari</dd></div>
          <div class="py-2 flex justify-between"><dt class="text-[var(--text-muted)]">Izin</dt><dd>{{ reportCard.permission_count }} hari</dd></div>
          <div class="py-2 flex justify-between"><dt class="text-[var(--text-muted)]">Alfa</dt><dd>{{ reportCard.absent_count }} hari</dd></div>
          <div v-if="reportCard.promotion_status" class="py-2 flex justify-between"><dt class="text-[var(--text-muted)]">Status</dt><dd class="uppercase font-semibold">{{ reportCard.promotion_status }}</dd></div>
        </dl>
      </AppCard>

      <AppCard class="lg:col-span-2" :padded="false">
        <iframe :src="route('admin.tpq.report.pdf', reportCard.id) + '?inline=1'" class="w-full h-[70vh] rounded-2xl" />
      </AppCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { FileText as FileTextIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'

defineProps({
  reportCard: { type: Object, required: true },
})
</script>
