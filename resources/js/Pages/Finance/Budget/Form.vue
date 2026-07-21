<template>
  <Head :title="isEdit ? 'Kelola RAB' : 'Buat RAB'" />

  <AdminLayout :title="isEdit ? 'Kelola RAB' : 'Buat RAB'">
    <PageHeader :title="isEdit ? 'Kelola RAB' : 'Buat RAB'" />

    <AppCard class="max-w-3xl">
      <form class="space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppInput v-model="form.name" label="Nama Anggaran" required :error="form.errors.name" />
          <AppSelect v-model="form.period_type" label="Tipe Periode" required :options="[{ label: 'Bulanan', value: 'monthly' }, { label: 'Tahunan', value: 'yearly' }, { label: 'Proyek', value: 'project' }]" :error="form.errors.period_type" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <AppInput v-model="form.start_date" type="date" label="Mulai" required :error="form.errors.start_date" />
          <AppInput v-model="form.end_date" type="date" label="Selesai" required :error="form.errors.end_date" />
          <AppSelect v-model="form.status" label="Status" required :options="[{ label: 'Draft', value: 'draft' }, { label: 'Aktif', value: 'active' }, { label: 'Ditutup', value: 'closed' }]" :error="form.errors.status" />
        </div>

        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-medium text-[var(--text-primary)]">Rincian Anggaran</label>
            <button type="button" class="text-sm text-primary-600 hover:underline" @click="addItem">+ Tambah Item</button>
          </div>

          <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_140px_auto] gap-2 mb-2 items-start">
            <AppSelect v-model="item.category_id" placeholder="Kategori" :options="categories.map((c) => ({ label: c.name, value: c.id }))" />
            <AppInput v-model="item.name" placeholder="Nama item" />
            <AppInput v-model.number="item.planned_amount" type="number" placeholder="Nominal" />
            <div class="flex items-center gap-2">
              <span v-if="realizationFor(item.category_id)" class="text-xs text-[var(--text-muted)] whitespace-nowrap">
                Realisasi: {{ formatCurrency(realizationFor(item.category_id)) }}
              </span>
              <button type="button" class="text-red-500 text-sm" @click="removeItem(index)">Hapus</button>
            </div>
          </div>
          <p v-if="form.errors.items" class="mt-1 text-xs text-red-500">{{ form.errors.items }}</p>
        </div>

        <div class="flex justify-between items-center pt-2 border-t border-[var(--border)]">
          <p class="text-sm text-[var(--text-muted)]">Total Rencana: <span class="font-semibold text-[var(--text-primary)]">{{ formatCurrency(totalPlanned) }}</span></p>
          <div class="flex gap-2">
            <Link :href="route('admin.finance.anggaran.index')" class="btn-secondary">Batal</Link>
            <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
          </div>
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
  budget: { type: Object, default: null },
  realizations: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
})

const isEdit = computed(() => props.budget !== null)

const form = useForm({
  name: props.budget?.name ?? '',
  period_type: props.budget?.period_type ?? 'monthly',
  start_date: props.budget?.start_date ?? '',
  end_date: props.budget?.end_date ?? '',
  status: props.budget?.status ?? 'draft',
  items: props.budget?.items?.length
    ? props.budget.items.map((item) => ({ category_id: item.category_id, name: item.name, planned_amount: item.planned_amount, notes: item.notes }))
    : [{ category_id: '', name: '', planned_amount: 0, notes: '' }],
})

const totalPlanned = computed(() => form.items.reduce((sum, item) => sum + Number(item.planned_amount || 0), 0))

function addItem() {
  form.items.push({ category_id: '', name: '', planned_amount: 0, notes: '' })
}

function removeItem(index) {
  form.items.splice(index, 1)
}

function realizationFor(categoryId) {
  return props.realizations[categoryId] ?? 0
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

function submit() {
  if (isEdit.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.finance.anggaran.update', props.budget.id))
  } else {
    form.post(route('admin.finance.anggaran.store'))
  }
}
</script>
