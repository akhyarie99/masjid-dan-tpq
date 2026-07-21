<template>
  <Head title="Input Nilai" />

  <AdminLayout title="Input Nilai">
    <PageHeader title="Input Nilai" description="Pilih kelas dan semester untuk mengisi nilai." />

    <AppCard class="max-w-md">
      <form class="space-y-4" @submit.prevent="go">
        <AppSelect v-model="classId" label="Kelas" required :options="classes.map((c) => ({ label: c.name, value: c.id }))" />
        <AppSelect v-model="semesterId" label="Semester" required :options="semesters.map((s) => ({ label: s.name, value: s.id }))" />
        <AppButton type="submit" class="w-full justify-center" :disabled="!classId || !semesterId">Buka Input Nilai</AppButton>
      </form>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

defineProps({
  classes: { type: Array, default: () => [] },
  semesters: { type: Array, default: () => [] },
})

const classId = ref('')
const semesterId = ref('')

function go() {
  router.get(route('admin.tpq.grade.show', [classId.value, semesterId.value]))
}
</script>
