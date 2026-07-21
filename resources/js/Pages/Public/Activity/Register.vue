<template>
  <Head :title="`Daftar - ${activity.name}`" />

  <PublicLayout>
    <div class="max-w-md mx-auto px-4 py-10 md:py-16">
      <div class="card p-6">
        <h1 class="text-xl font-bold text-[var(--text-primary)]">{{ activity.name }}</h1>
        <p class="text-sm text-[var(--text-muted)] mt-1">{{ formatDate(activity.start_at) }} · {{ activity.location }}</p>
        <p v-if="activity.description" class="text-sm text-[var(--text-muted)] mt-3">{{ activity.description }}</p>
        <p v-if="activity.quota" class="text-xs text-[var(--text-muted)] mt-2">{{ registeredCount }} / {{ activity.quota }} pendaftar</p>

        <div v-if="submitted" class="text-center py-6">
          <CheckCircleIcon class="w-12 h-12 text-green-600 mx-auto mb-3" />
          <p class="font-medium text-[var(--text-primary)]">Pendaftaran berhasil!</p>
        </div>

        <form v-else class="space-y-4 mt-6" @submit.prevent="submit">
          <AppInput v-model="form.name" label="Nama" required :error="form.errors.name" />
          <AppInput v-model="form.phone" label="No. HP" required :error="form.errors.phone" />
          <AppInput v-model="form.email" type="email" label="Email (opsional)" :error="form.errors.email" />
          <AppButton type="submit" class="w-full justify-center" :loading="form.processing">Daftar Sekarang</AppButton>
        </form>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { CheckCircle as CheckCircleIcon } from 'lucide-vue-next'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  activity: { type: Object, required: true },
  registeredCount: { type: Number, default: 0 },
})

const submitted = ref(false)

const form = useForm({ name: '', phone: '', email: '' })

function submit() {
  form.post(route('public.activity.register.store', props.activity.id), {
    preserveScroll: true,
    onSuccess: () => { submitted.value = true },
  })
}

function formatDate(value) {
  return dayjs(value).format('dddd, DD MMM YYYY, HH:mm')
}
</script>
