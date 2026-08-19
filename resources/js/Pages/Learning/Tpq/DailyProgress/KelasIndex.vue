<template>
  <Head title="Mengaji Harian - Pilih Kelas" />

  <AdminLayout title="Mengaji Harian">
    <TpqSubNav />
    <PageHeader title="Mengaji Harian per Kelas" description="Isi progres satu kelas sekaligus dari daftar — cocok untuk kelas kecil atau rekap." />

    <AppCard class="max-w-md">
      <form class="space-y-4" @submit.prevent="go">
        <AppSelect v-model="classId" label="Kelas" required :options="classes.map((c) => ({ label: c.name, value: c.id }))" />
        <AppInput v-model="date" type="date" label="Tanggal" required />
        <AppButton type="submit" class="w-full justify-center" :disabled="!classId">Buka Input Harian</AppButton>
      </form>
    </AppCard>

    <p class="max-w-md mt-4 text-sm text-center">
      <Link :href="route('admin.tpq.daily-progress.index')" class="text-primary-600 hover:underline">
        Kembali ke scan/cari santri
      </Link>
    </p>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
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
