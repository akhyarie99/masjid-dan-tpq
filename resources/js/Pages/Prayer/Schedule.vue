<template>
  <Head title="Jadwal Shalat" />

  <AdminLayout title="Jadwal Shalat">
    <PageHeader title="Jadwal Shalat" :description="`Jadwal shalat bulan ${monthLabel}`">
      <template #actions>
        <AppButton :loading="generating" @click="generate">
          <RefreshCwIcon class="w-4 h-4" /> Generate 30 Hari
        </AppButton>
      </template>
    </PageHeader>

    <AppCard v-if="!masjid.latitude || !masjid.longitude" class="mb-6">
      <AppAlert variant="warning">
        Koordinat masjid belum diatur. Silakan atur di menu
        <Link v-if="route().has('admin.settings.index')" :href="route('admin.settings.index')" class="underline font-medium">Pengaturan</Link>
        agar jadwal shalat dapat dihitung otomatis.
      </AppAlert>
    </AppCard>

    <AppCard :padded="false">
      <AppTable :columns="columns" :rows="schedules" empty-text="Belum ada jadwal shalat untuk bulan ini.">
        <template #cell-date="{ value }">
          {{ formatDate(value) }}
        </template>
      </AppTable>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { RefreshCw as RefreshCwIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppTable from '@/Components/UI/AppTable.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import AppAlert from '@/Components/UI/AppAlert.vue'

const props = defineProps({
  schedules: { type: Array, required: true },
  month: { type: Number, required: true },
  year: { type: Number, required: true },
  masjid: { type: Object, required: true },
})

const generating = ref(false)

const columns = [
  { key: 'date', label: 'Tanggal' },
  { key: 'fajr', label: 'Subuh' },
  { key: 'sunrise', label: 'Terbit' },
  { key: 'dhuhr', label: 'Dzuhur' },
  { key: 'asr', label: 'Ashar' },
  { key: 'maghrib', label: 'Maghrib' },
  { key: 'isha', label: 'Isya' },
]

const monthLabel = computed(() => dayjs(`${props.year}-${String(props.month).padStart(2, '0')}-01`).format('MMMM YYYY'))

function formatDate(value) {
  return dayjs(value).format('dddd, DD MMM YYYY')
}

function generate() {
  generating.value = true
  router.post(route('admin.prayer.schedule.generate'), {}, {
    preserveScroll: true,
    onFinish: () => { generating.value = false },
  })
}
</script>
