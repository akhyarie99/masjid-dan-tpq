<template>
  <Head :title="`Raport - ${reportCard.student.name}`" />

  <WaliLayout>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-lg font-bold text-[var(--text-primary)]">Raport {{ reportCard.student.name }}</h1>
        <p class="text-sm text-[var(--text-muted)]">{{ reportCard.class.name }} · {{ reportCard.semester.name }}</p>
      </div>
      <a :href="route('wali.reportcard.pdf', reportCard.id)" class="btn-primary">Download PDF</a>
    </div>

    <div class="card !p-0 overflow-hidden">
      <iframe :src="route('wali.reportcard.pdf', reportCard.id) + '?inline=1'" class="w-full h-[75vh]" />
    </div>
  </WaliLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import WaliLayout from '@/Layouts/WaliLayout.vue'

defineProps({
  reportCard: { type: Object, required: true },
})
</script>
