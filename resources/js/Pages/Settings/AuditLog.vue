<template>
  <Head title="Log Aktivitas" />

  <AdminLayout title="Log Aktivitas">
    <PageHeader title="Log Aktivitas" description="Riwayat perubahan data oleh pengguna (audit trail)." />

    <AppCard class="mb-6">
      <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <AppSelect v-model="filters.user_id" placeholder="Semua Pengguna" :options="users.map((u) => ({ label: u.name, value: u.id }))" />
        <AppInput v-model="filters.model" placeholder="Nama model (cth: Transaction)" />
        <AppSelect v-model="filters.event" placeholder="Semua Aksi" :options="[{ label: 'Dibuat', value: 'created' }, { label: 'Diubah', value: 'updated' }, { label: 'Dihapus', value: 'deleted' }]" />
        <AppInput v-model="filters.from" type="date" />
        <AppInput v-model="filters.to" type="date" />
      </div>
    </AppCard>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Model</th><th>Keterangan</th></tr></thead>
          <tbody>
            <tr v-if="logs.data.length === 0">
              <td colspan="5" class="text-center text-[var(--text-muted)] py-8">Belum ada aktivitas tercatat.</td>
            </tr>
            <tr v-for="log in logs.data" :key="log.id">
              <td class="whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
              <td>{{ log.causer ?? 'Sistem' }}</td>
              <td><AppBadge :variant="eventVariant(log.event)">{{ eventLabel(log.event) }}</AppBadge></td>
              <td>{{ log.subject_type }}</td>
              <td>{{ log.description }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <div class="mt-4">
      <AppPagination :links="logs.links" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'

const props = defineProps({
  logs: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  users: { type: Array, default: () => [] },
})

const filters = reactive({
  user_id: props.filters.user_id ?? '',
  model: props.filters.model ?? '',
  event: props.filters.event ?? '',
  from: props.filters.from ?? '',
  to: props.filters.to ?? '',
})

watch(filters, () => {
  router.get(route('admin.settings.audit-log'), { ...filters }, { preserveState: true, replace: true })
}, { deep: true })

function eventLabel(event) {
  return { created: 'Dibuat', updated: 'Diubah', deleted: 'Dihapus' }[event] ?? event
}

function eventVariant(event) {
  return { created: 'green', updated: 'blue', deleted: 'red' }[event] ?? 'gray'
}

function formatDate(value) {
  return dayjs(value).format('DD MMM YYYY, HH:mm')
}
</script>
