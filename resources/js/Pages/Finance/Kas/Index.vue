<template>
  <Head title="Rekening Kas" />

  <AdminLayout title="Rekening Kas">
    <PageHeader title="Rekening Kas" description="Kelola kas tunai dan rekening bank masjid.">
      <template #actions>
        <AppButton @click="openCreate"><PlusIcon class="w-4 h-4" /> Tambah Rekening</AppButton>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="account in accounts" :key="account.id" class="card p-5">
        <div class="flex items-start justify-between">
          <div>
            <p class="font-semibold text-[var(--text-primary)]">{{ account.name }}</p>
            <p class="text-xs text-[var(--text-muted)] mt-0.5">{{ account.type === 'cash' ? 'Kas Tunai' : (account.bank_name ?? 'Bank') }}</p>
            <p v-if="account.account_number" class="text-xs text-[var(--text-muted)]">{{ account.account_number }} a.n. {{ account.account_name }}</p>
          </div>
          <AppBadge :variant="account.is_active ? 'green' : 'gray'">{{ account.is_active ? 'Aktif' : 'Nonaktif' }}</AppBadge>
        </div>
        <p class="text-2xl font-bold text-primary-600 tabular-nums mt-4">{{ formatCurrency(account.current_balance) }}</p>
        <button class="text-sm text-primary-600 hover:underline mt-3" @click="openEdit(account)">Edit</button>
      </div>
    </div>

    <AppModal :show="showModal" :title="editing ? 'Edit Rekening Kas' : 'Tambah Rekening Kas'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Rekening" required :error="form.errors.name" />
        <AppSelect v-model="form.type" label="Tipe" required :options="[{ label: 'Kas Tunai', value: 'cash' }, { label: 'Bank', value: 'bank' }]" :error="form.errors.type" />
        <template v-if="form.type === 'bank'">
          <AppInput v-model="form.bank_name" label="Nama Bank" :error="form.errors.bank_name" />
          <AppInput v-model="form.account_number" label="Nomor Rekening" :error="form.errors.account_number" />
          <AppInput v-model="form.account_name" label="Atas Nama" :error="form.errors.account_name" />
        </template>
        <AppInput v-model.number="form.initial_balance" type="number" label="Saldo Awal (Rp)" required :error="form.errors.initial_balance" />
        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input v-model="form.is_active" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
          Aktif
        </label>
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showModal = false">Batal</AppButton>
        <AppButton :loading="form.processing" @click="submit">Simpan</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Plus as PlusIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'

defineProps({
  accounts: { type: Array, default: () => [] },
})

const showModal = ref(false)
const editing = ref(null)

const form = useForm({
  name: '', type: 'cash', bank_name: '', account_number: '', account_name: '', initial_balance: 0, is_active: true,
})

function openCreate() {
  editing.value = null
  form.reset()
  showModal.value = true
}

function openEdit(account) {
  editing.value = account
  form.name = account.name
  form.type = account.type
  form.bank_name = account.bank_name
  form.account_number = account.account_number
  form.account_name = account.account_name
  form.initial_balance = account.initial_balance
  form.is_active = account.is_active
  showModal.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.finance.kas.update', editing.value.id), options)
  } else {
    form.post(route('admin.finance.kas.store'), options)
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}
</script>
