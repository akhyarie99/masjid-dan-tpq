<template>
  <Head title="Laporan LPJ Tahunan" />

  <AdminLayout title="Laporan LPJ Tahunan">
    <PageHeader title="Laporan Pertanggungjawaban (LPJ) Tahunan" description="Generate laporan lengkap DKM untuk satu tahun berjalan." />

    <AppCard class="max-w-md">
      <form class="space-y-4" @submit.prevent="generate">
        <AppInput v-model.number="year" type="number" label="Tahun" required />
        <AppButton type="submit" class="w-full justify-center" :loading="loading">
          <FileTextIcon class="w-4 h-4" /> Generate & Download LPJ
        </AppButton>
      </form>
      <p class="text-xs text-[var(--text-muted)] mt-4">
        Laporan mencakup: kata pengantar, susunan pengurus, laporan keuangan, kegiatan, aset, TPQ, program sosial, dan penutup.
      </p>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { FileText as FileTextIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  currentYear: { type: Number, required: true },
})

const year = ref(props.currentYear)
const loading = ref(false)

function generate() {
  loading.value = true
  window.location.href = route('admin.report.lpj.generate', { year: year.value })
  setTimeout(() => { loading.value = false }, 1500)
}
</script>
