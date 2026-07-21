<template>
  <Head title="Broadcast WhatsApp" />

  <AdminLayout title="Broadcast WhatsApp">
    <PageHeader title="Broadcast WhatsApp" description="Kirim pesan ke jamaah secara massal." />

    <AppCard class="max-w-xl">
      <form class="space-y-4" @submit.prevent="submit">
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">Target Penerima</label>
          <div class="grid grid-cols-2 gap-2">
            <button
              v-for="target in targets"
              :key="target.value"
              type="button"
              class="py-2 rounded-lg text-sm font-medium border"
              :class="form.target === target.value ? 'bg-primary-600 border-primary-600 text-white' : 'border-[var(--border)] text-[var(--text-primary)]'"
              @click="form.target = target.value"
            >
              {{ target.label }}
            </button>
          </div>
        </div>

        <AppSelect v-if="form.target === 'rt'" v-model="form.rt" label="Pilih RT" :options="rtOptions.map((rt) => ({ label: `RT ${rt}`, value: rt }))" />
        <AppSelect v-if="form.target === 'tag'" v-model="form.tag" label="Pilih Tag" :options="tagOptions.map((tag) => ({ label: tag, value: tag }))" />

        <div v-if="form.target === 'manual'">
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Nomor HP (satu per baris)</label>
          <textarea v-model="form.manual_numbers" rows="4" class="input" placeholder="08123456789&#10;08987654321" />
        </div>

        <p v-if="form.target !== 'manual'" class="text-xs text-[var(--text-muted)]">Perkiraan penerima: {{ totalActive }} jamaah aktif (sebelum filter RT/tag).</p>

        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Pesan</label>
          <textarea v-model="form.message" rows="5" class="input" required />
          <p v-if="form.errors.message" class="mt-1 text-xs text-red-500">{{ form.errors.message }}</p>
        </div>

        <div class="flex justify-end pt-2">
          <AppButton type="submit" :loading="form.processing">Kirim Broadcast</AppButton>
        </div>
      </form>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

defineProps({
  rtOptions: { type: Array, default: () => [] },
  tagOptions: { type: Array, default: () => [] },
  totalActive: { type: Number, default: 0 },
})

const targets = [
  { label: 'Semua Jamaah Aktif', value: 'all' },
  { label: 'Per RT', value: 'rt' },
  { label: 'Per Tag', value: 'tag' },
  { label: 'Input Manual', value: 'manual' },
]

const form = useForm({ target: 'all', rt: '', tag: '', manual_numbers: '', message: '' })

function submit() {
  form.post(route('admin.jamaah.broadcast.send'), { preserveScroll: true })
}
</script>
