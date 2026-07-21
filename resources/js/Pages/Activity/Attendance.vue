<template>
  <Head :title="`Presensi - ${activity.name}`" />

  <AdminLayout title="Presensi Kegiatan">
    <PageHeader :title="activity.name" :description="activity.location">
      <template #actions>
        <a :href="route('admin.activity.attendance.export', activity.id)" class="btn-secondary"><FileTextIcon class="w-4 h-4" /> Export PDF</a>
      </template>
    </PageHeader>

    <div class="card p-5 mb-6 flex items-center justify-between">
      <div>
        <p class="text-sm text-[var(--text-muted)]">Live Counter</p>
        <p class="text-2xl font-bold text-primary-600 mt-1">{{ attendedCount }} <span class="text-base text-[var(--text-muted)] font-normal">hadir dari {{ registrations.length }} pendaftar</span></p>
      </div>
      <RefreshCwIcon class="w-5 h-5 text-[var(--text-muted)]" :class="{ 'animate-spin': polling }" />
    </div>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Nama</th><th>No. HP</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <tr v-if="registrations.length === 0">
              <td colspan="4" class="text-center text-[var(--text-muted)] py-8">Belum ada pendaftar.</td>
            </tr>
            <tr v-for="registration in registrations" :key="registration.id">
              <td>{{ registration.name }}</td>
              <td>{{ registration.phone }}</td>
              <td><AppBadge :variant="registration.is_attended ? 'green' : 'gray'">{{ registration.is_attended ? 'Hadir' : 'Belum Hadir' }}</AppBadge></td>
              <td>
                <button class="text-primary-600 text-sm hover:underline" @click="toggle(registration)">
                  {{ registration.is_attended ? 'Batalkan' : 'Tandai Hadir' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { FileText as FileTextIcon, RefreshCw as RefreshCwIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'

const props = defineProps({
  activity: { type: Object, required: true },
  registrations: { type: Array, default: () => [] },
})

const polling = ref(false)
const attendedCount = computed(() => props.registrations.filter((r) => r.is_attended).length)

let timer
onMounted(() => {
  timer = setInterval(() => {
    polling.value = true
    router.reload({ only: ['registrations'], onFinish: () => { polling.value = false } })
  }, 30000)
})
onUnmounted(() => clearInterval(timer))

function toggle(registration) {
  router.post(route('admin.activity.attendance.store', props.activity.id), {
    registration_id: registration.id,
    is_attended: !registration.is_attended,
  }, { preserveScroll: true })
}
</script>
