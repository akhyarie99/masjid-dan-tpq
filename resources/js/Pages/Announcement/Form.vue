<template>
  <Head :title="isEdit ? 'Edit Pengumuman' : 'Buat Pengumuman'" />

  <AdminLayout :title="isEdit ? 'Edit Pengumuman' : 'Buat Pengumuman'">
    <PageHeader :title="isEdit ? 'Edit Pengumuman' : 'Buat Pengumuman'" />

    <AppCard class="max-w-2xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.title" label="Judul" required :error="form.errors.title" />

        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Isi Pengumuman</label>
          <textarea v-model="form.content" rows="5" class="input" required />
          <p v-if="form.errors.content" class="mt-1 text-xs text-red-500">{{ form.errors.content }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppSelect v-model="form.type" label="Tipe" required :options="[{ label: 'Umum', value: 'umum' }, { label: 'Kegiatan', value: 'kegiatan' }, { label: 'Duka Cita', value: 'duka' }, { label: 'Penting', value: 'urgent' }]" :error="form.errors.type" />
          <AppInput v-model="form.expired_at" type="date" label="Berlaku Sampai (opsional)" :error="form.errors.expired_at" />
        </div>

        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input v-model="form.is_published" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
          Terbitkan sekarang
        </label>

        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input v-model="form.send_whatsapp" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
          Kirim broadcast WhatsApp ke jamaah saat diterbitkan
        </label>

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('pengumuman.index')" class="btn-secondary">Batal</Link>
          <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
        </div>
      </form>
    </AppCard>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  announcement: { type: Object, default: null },
})

const isEdit = computed(() => props.announcement !== null)

const form = useForm({
  title: props.announcement?.title ?? '',
  content: props.announcement?.content ?? '',
  type: props.announcement?.type ?? 'umum',
  expired_at: props.announcement?.expired_at ?? '',
  is_published: props.announcement?.is_published ?? false,
  send_whatsapp: props.announcement?.send_whatsapp ?? false,
})

function submit() {
  if (isEdit.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('pengumuman.update', props.announcement.id))
  } else {
    form.post(route('pengumuman.store'))
  }
}
</script>
