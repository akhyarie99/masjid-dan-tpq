<template>
  <Head :title="isEdit ? 'Edit Transaksi' : 'Tambah Transaksi'" />

  <AdminLayout :title="isEdit ? 'Edit Transaksi' : 'Tambah Transaksi'">
    <PageHeader :title="isEdit ? 'Edit Transaksi' : 'Tambah Transaksi'" />

    <AppCard class="max-w-2xl">
      <!-- Toggle Pemasukan/Pengeluaran -->
      <div class="grid grid-cols-2 gap-2 mb-6">
        <button
          type="button"
          class="py-2.5 rounded-lg text-sm font-medium border transition-colors"
          :class="form.type === 'income' ? 'bg-green-50 border-green-500 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'border-[var(--border)] text-[var(--text-muted)]'"
          @click="form.type = 'income'"
        >
          Pemasukan
        </button>
        <button
          type="button"
          class="py-2.5 rounded-lg text-sm font-medium border transition-colors"
          :class="form.type === 'expense' ? 'bg-red-50 border-red-500 text-red-700 dark:bg-red-900/20 dark:text-red-300' : 'border-[var(--border)] text-[var(--text-muted)]'"
          @click="form.type = 'expense'"
        >
          Pengeluaran
        </button>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.transaction_date" type="date" label="Tanggal" required :error="form.errors.transaction_date" />
          <AppSelect v-model="form.kas_account_id" label="Rekening Kas" required :options="kasAccounts.map((k) => ({ label: k.name, value: k.id }))" :error="form.errors.kas_account_id" />
        </div>

        <AppSelect
          v-model="form.category_id"
          label="Kategori"
          required
          :options="filteredCategories.map((c) => ({ label: c.name, value: c.id }))"
          :error="form.errors.category_id"
        />

        <AppInput v-model.number="form.amount" type="number" label="Nominal (Rp)" required :error="form.errors.amount" />
        <p class="text-sm text-[var(--text-muted)] -mt-2">{{ formatCurrency(form.amount || 0) }}</p>

        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Keterangan</label>
          <textarea v-model="form.description" rows="3" class="input" required />
          <p v-if="form.errors.description" class="mt-1 text-xs text-red-500">{{ form.errors.description }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Upload Bukti</label>
          <input type="file" class="input" @input="form.proof_file = $event.target.files[0]" />
          <p v-if="form.errors.proof_file" class="mt-1 text-xs text-red-500">{{ form.errors.proof_file }}</p>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.finance.transaksi.index')" class="btn-secondary">Batal</Link>
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
  transaction: { type: Object, default: null },
  kasAccounts: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

const isEdit = computed(() => props.transaction !== null)

const form = useForm({
  type: props.transaction?.type ?? 'income',
  kas_account_id: props.transaction?.kas_account_id ?? '',
  category_id: props.transaction?.category_id ?? '',
  amount: props.transaction?.amount ?? '',
  description: props.transaction?.description ?? '',
  transaction_date: props.transaction?.transaction_date ?? new Date().toISOString().slice(0, 10),
  proof_file: null,
})

const filteredCategories = computed(() => props.categories.filter((c) => c.type === form.type))

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

function submit() {
  if (isEdit.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.finance.transaksi.update', props.transaction.id))
  } else {
    form.post(route('admin.finance.transaksi.store'))
  }
}
</script>
