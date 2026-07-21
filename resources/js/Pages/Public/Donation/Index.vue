<template>
  <Head title="Donasi" />

  <PublicLayout>
    <div class="max-w-lg mx-auto px-4 py-10 md:py-16">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">💚 Donasi / Infaq</h1>
        <p class="text-sm text-[var(--text-muted)] mt-1">Untuk {{ masjid.name }}</p>
      </div>

      <div v-if="submitted" class="card p-6 text-center">
        <CheckCircleIcon class="w-12 h-12 text-green-600 mx-auto mb-3" />
        <p class="font-semibold text-[var(--text-primary)]">Terima kasih!</p>
        <p class="text-sm text-[var(--text-muted)] mt-1">Silakan selesaikan pembayaran pada jendela yang terbuka. Status donasi akan diperbarui otomatis setelah pembayaran berhasil.</p>
        <button class="btn-secondary mt-4" @click="reset">Donasi Lagi</button>
      </div>

      <form v-else class="card p-6 space-y-5" @submit.prevent="submit">
        <!-- Nominal -->
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">Pilih Nominal</label>
          <div class="grid grid-cols-2 gap-2">
            <button
              v-for="preset in presets"
              :key="preset"
              type="button"
              class="py-2.5 rounded-lg text-sm font-medium border transition-colors"
              :class="form.amount === preset ? 'bg-primary-600 border-primary-600 text-white' : 'border-[var(--border)] text-[var(--text-primary)]'"
              @click="form.amount = preset"
            >
              {{ formatCurrency(preset) }}
            </button>
          </div>
          <AppInput v-model.number="customAmount" type="number" placeholder="Nominal bebas (min Rp10.000)" class="mt-2" @input="form.amount = customAmount" />
        </div>

        <AppSelect
          v-model="form.purpose"
          label="Tujuan Donasi"
          :options="[{ label: 'Umum', value: 'Umum' }, { label: 'Renovasi', value: 'Renovasi' }, { label: 'Sosial', value: 'Sosial' }, { label: 'Kegiatan', value: 'Kegiatan' }]"
        />

        <AppInput v-model="form.donor_name" label="Nama (opsional, kosongkan untuk anonim)" />
        <AppInput v-model="form.donor_phone" label="Nomor HP" required />

        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">Metode Pembayaran</label>
          <div class="grid grid-cols-3 gap-2">
            <button
              v-for="method in methods"
              :key="method.value"
              type="button"
              class="py-2.5 rounded-lg text-xs font-medium border transition-colors"
              :class="form.payment_method === method.value ? 'bg-primary-600 border-primary-600 text-white' : 'border-[var(--border)] text-[var(--text-primary)]'"
              @click="form.payment_method = method.value"
            >
              {{ method.label }}
            </button>
          </div>
        </div>

        <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

        <AppButton type="submit" class="w-full justify-center" :loading="loading" :disabled="!canSubmit">
          Lanjutkan Pembayaran
        </AppButton>
      </form>
    </div>
  </PublicLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import { CheckCircle as CheckCircleIcon } from 'lucide-vue-next'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  masjid: { type: Object, required: true },
  midtrans: { type: Object, required: true },
})

const presets = [10000, 25000, 50000, 100000]
const methods = [
  { label: 'QRIS', value: 'qris' },
  { label: 'VA BRI', value: 'va_bri' },
  { label: 'VA BNI', value: 'va_bni' },
]

const form = ref({
  amount: presets[1],
  purpose: 'Umum',
  donor_name: '',
  donor_phone: '',
  payment_method: 'qris',
})
const customAmount = ref('')
const loading = ref(false)
const submitted = ref(false)
const error = ref('')

const canSubmit = computed(() => form.value.amount >= 10000 && form.value.donor_phone.length >= 8)

onMounted(() => {
  const script = document.createElement('script')
  script.src = props.midtrans.isProduction
    ? 'https://app.midtrans.com/snap/snap.js'
    : 'https://app.sandbox.midtrans.com/snap/snap.js'
  script.setAttribute('data-client-key', props.midtrans.clientKey ?? '')
  document.head.appendChild(script)
})

async function submit() {
  loading.value = true
  error.value = ''

  try {
    const response = await axios.post(route('public.donation.store'), form.value)
    const { snap_token: snapToken } = response.data

    if (!snapToken || !window.snap) {
      error.value = 'Gagal memuat halaman pembayaran. Silakan coba lagi.'
      loading.value = false
      return
    }

    window.snap.pay(snapToken, {
      onSuccess: () => { submitted.value = true },
      onPending: () => { submitted.value = true },
      onError: () => { error.value = 'Pembayaran gagal. Silakan coba lagi.' },
      onClose: () => {},
    })
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Terjadi kesalahan. Silakan coba lagi.'
  } finally {
    loading.value = false
  }
}

function reset() {
  submitted.value = false
  form.value = { amount: presets[1], purpose: 'Umum', donor_name: '', donor_phone: '', payment_method: 'qris' }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}
</script>
