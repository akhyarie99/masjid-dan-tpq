<template>
  <Head title="Kalender Kegiatan" />

  <AdminLayout title="Kalender Kegiatan">
    <PageHeader title="Kalender Kegiatan" description="Kelola jadwal kegiatan masjid.">
      <template #actions>
        <Link :href="route('admin.activity.create')" class="btn-primary"><PlusIcon class="w-4 h-4" /> Tambah Kegiatan</Link>
      </template>
    </PageHeader>

    <AppCard :padded="false" class="p-2 md:p-4">
      <FullCalendar :options="calendarOptions" />
    </AppCard>

    <AppModal :show="selected !== null" :title="selected?.title" @close="selected = null">
      <div v-if="selected" class="space-y-2 text-sm">
        <p><strong>Kategori:</strong> {{ categoryLabel(selected.category) }}</p>
        <p><strong>Lokasi:</strong> {{ selected.location }}</p>
        <p><strong>Waktu:</strong> {{ formatDate(selected.start) }}</p>
        <p v-if="selected.pic_name"><strong>PIC:</strong> {{ selected.pic_name }} ({{ selected.pic_phone }})</p>
        <p v-if="selected.quota"><strong>Kuota:</strong> {{ selected.quota }}</p>
      </div>
      <template #footer>
        <Link :href="route('admin.activity.attendance', selected?.id)" class="btn-secondary">Presensi</Link>
        <Link :href="route('admin.activity.qr', selected?.id)" class="btn-secondary">QR</Link>
        <Link :href="route('admin.activity.edit', selected?.id)" class="btn-primary">Edit</Link>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Plus as PlusIcon } from 'lucide-vue-next'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import listPlugin from '@fullcalendar/list'
import interactionPlugin from '@fullcalendar/interaction'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppModal from '@/Components/UI/AppModal.vue'

const props = defineProps({
  events: { type: Array, default: () => [] },
})

const selected = ref(null)

const categoryColors = {
  kajian_rutin: '#16a34a',
  pengajian_akbar: '#d97706',
  sosial: '#3b82f6',
  phbi: '#8b5cf6',
  rapat: '#64748b',
  lainnya: '#ec4899',
}

const calendarEvents = computed(() => props.events.map((event) => ({
  id: event.id,
  title: event.title,
  start: event.start,
  end: event.end,
  color: categoryColors[event.category] ?? '#16a34a',
  extendedProps: event,
})))

const calendarOptions = {
  plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
  initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
  headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
  events: calendarEvents.value,
  height: 'auto',
  eventClick: (info) => {
    selected.value = info.event.extendedProps
  },
}

function categoryLabel(category) {
  return {
    kajian_rutin: 'Kajian Rutin', pengajian_akbar: 'Pengajian Akbar', sosial: 'Sosial',
    phbi: 'PHBI', rapat: 'Rapat', lainnya: 'Lainnya',
  }[category] ?? category
}

function formatDate(value) {
  return dayjs(value).format('dddd, DD MMM YYYY, HH:mm')
}
</script>
