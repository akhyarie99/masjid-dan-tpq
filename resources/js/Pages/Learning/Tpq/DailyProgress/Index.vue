<template>
  <Head title="Mengaji Harian" />

  <AdminLayout title="Mengaji Harian">
    <TpqSubNav />
    <PageHeader title="Mengaji Harian" description="Pilih kelas dan tanggal untuk mengisi progres mengaji harian santri." />

    <AppCard class="max-w-md">
      <form class="space-y-4" @submit.prevent="go">
        <AppSelect v-model="classId" label="Kelas" required :options="classes.map((c) => ({ label: c.name, value: c.id }))" />
        <AppInput v-model="date" type="date" label="Tanggal" required />
        <AppButton type="submit" class="w-full justify-center" :disabled="!classId">Buka Input Harian</AppButton>
      </form>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import TpqSubNav from '@/Components/Shared/TpqSubNav.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

defineProps({
  classes: { type: Array, default: () => [] },
})

const classId = ref('')
const date = ref(new Date().toISOString().slice(0, 10))

function go() {
  router.get(route('admin.tpq.daily-progress.show', classId.value), { date: date.value })
}
</script>
