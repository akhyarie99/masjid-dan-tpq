<template>
  <Head :title="`Presensi - ${activity.name}`" />

  <PublicLayout>
    <div class="max-w-md mx-auto px-4 py-10 md:py-16">
      <div class="card p-6">
        <h1 class="text-xl font-bold text-[var(--text-primary)] text-center">✅ Presensi Kehadiran</h1>
        <p class="text-sm text-[var(--text-muted)] text-center mt-1">{{ activity.name }} · {{ activity.location }}</p>

        <div v-if="submitted" class="text-center py-6">
          <CheckCircleIcon class="w-12 h-12 text-green-600 mx-auto mb-3" />
          <p class="font-medium text-[var(--text-primary)]">Kehadiran Anda tercatat. Jazakumullahu khairan!</p>
        </div>

        <form v-else class="space-y-4 mt-6" @submit.prevent="submit">
          <AppInput v-model="form.name" label="Nama" required :error="form.errors.name" />
          <AppInput v-model="form.phone" label="No. HP" required :error="form.errors.phone" />
          <AppButton type="submit" class="w-full justify-center" :loading="form.processing">Presensi</AppButton>
        </form>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { CheckCircle as CheckCircleIcon } from 'lucide-vue-next'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  activity: { type: Object, required: true },
  token: { type: String, required: true },
})

const submitted = ref(false)

const form = useForm({ name: '', phone: '' })

function submit() {
  form.post(route('public.activity.checkin.store', { activity: props.activity.id, token: props.token }), {
    preserveScroll: true,
    onSuccess: () => { submitted.value = true },
  })
}
</script>
