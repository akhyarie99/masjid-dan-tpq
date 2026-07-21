<template>
  <Head title="Qurban" />

  <AdminLayout title="Qurban">
    <PageHeader title="Qurban" :description="`Tahun ${year} H`">
      <template #actions>
        <a :href="route('admin.ramadhan.qurban.export-pdf', { year })" class="btn-secondary"><FileTextIcon class="w-4 h-4" /> Daftar Shohibul (PDF)</a>
        <a :href="route('admin.ramadhan.qurban.distribution-report', { year })" class="btn-secondary"><FileTextIcon class="w-4 h-4" /> Laporan Distribusi</a>
        <AppButton @click="showAdd = true"><PlusIcon class="w-4 h-4" /> Daftar Qurban</AppButton>
      </template>
    </PageHeader>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-primary-600">{{ summary.totalShohibul }}</p>
        <p class="text-xs text-[var(--text-muted)]">Total Shohibul</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-[var(--text-primary)]">{{ summary.sapiEkor }} ekor</p>
        <p class="text-xs text-[var(--text-muted)]">Sapi ({{ summary.sapiShares }}/7 bagian)</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-[var(--text-primary)]">{{ summary.kambingEkor }} ekor</p>
        <p class="text-xs text-[var(--text-muted)]">Kambing</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-lg font-bold text-green-600">{{ summary.totalDistributedKg }} kg</p>
        <p class="text-xs text-[var(--text-muted)]">Terdistribusi ({{ summary.totalPenerima }} penerima)</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <AppCard title="Pendaftaran Shohibul" :padded="false">
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>Nama</th><th>Hewan</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <tr v-if="registrations.length === 0"><td colspan="4" class="text-center text-[var(--text-muted)] py-8">Belum ada pendaftaran.</td></tr>
              <tr v-for="registration in registrations" :key="registration.id">
                <td>{{ registration.shohibul_name }}</td>
                <td class="capitalize">{{ registration.animal_type }} {{ registration.animal_type === 'sapi' ? `(${registration.share_count}/7)` : '' }}</td>
                <td><AppBadge :variant="registration.payment_status === 'paid' ? 'green' : 'yellow'">{{ registration.payment_status === 'paid' ? 'Lunas' : 'Belum Lunas' }}</AppBadge></td>
                <td><button class="text-red-500 text-sm hover:underline" @click="deleteRegistration(registration)">Hapus</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </AppCard>

      <AppCard title="Distribusi Daging">
        <template #actions>
          <button class="text-sm text-primary-600 hover:underline" @click="showDistribute = true">+ Catat Distribusi</button>
        </template>
        <div v-if="distributions.length === 0" class="text-center text-sm text-[var(--text-muted)] py-8">Belum ada distribusi.</div>
        <ul v-else class="space-y-2">
          <li v-for="distribution in distributions" :key="distribution.id" class="flex items-center justify-between text-sm">
            <span>{{ distribution.recipient_name }} <span class="text-xs text-[var(--text-muted)]">({{ distribution.weight_kg ?? '-' }} kg)</span></span>
            <a :href="route('admin.ramadhan.qurban.distribusi.label', distribution.id)" target="_blank" class="text-primary-600 hover:underline">Cetak Label</a>
          </li>
        </ul>
      </AppCard>
    </div>

    <AppModal :show="showAdd" title="Daftar Qurban" @close="showAdd = false">
      <form class="space-y-4" @submit.prevent="submitAdd">
        <AppInput v-model="addForm.shohibul_name" label="Nama Shohibul" required :error="addForm.errors.shohibul_name" />
        <AppInput v-model="addForm.phone" label="No. HP" required :error="addForm.errors.phone" />
        <AppSelect v-model="addForm.animal_type" label="Jenis Hewan" required :options="[{ label: 'Sapi', value: 'sapi' }, { label: 'Kambing', value: 'kambing' }]" :error="addForm.errors.animal_type" />
        <AppSelect v-if="addForm.animal_type === 'sapi'" v-model.number="addForm.share_count" label="Jumlah Bagian (1-7)" :options="[1,2,3,4,5,6,7].map((n) => ({ label: String(n), value: n }))" />
        <AppInput v-model="addForm.animal_name" label="Nama Hewan (opsional)" :error="addForm.errors.animal_name" />
        <AppSelect v-model="addForm.payment_status" label="Status Pembayaran" required :options="[{ label: 'Belum Lunas', value: 'pending' }, { label: 'Lunas', value: 'paid' }]" />
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showAdd = false">Batal</AppButton>
        <AppButton :loading="addForm.processing" @click="submitAdd">Daftar</AppButton>
      </template>
    </AppModal>

    <AppModal :show="showDistribute" title="Catat Distribusi Daging" @close="showDistribute = false">
      <form class="space-y-4" @submit.prevent="submitDistribute">
        <AppSelect v-model="distForm.jamaah_id" label="Pilih dari Database Jamaah (opsional)" placeholder="Atau isi manual" :options="jamaahOptions.map((j) => ({ label: j.name, value: j.id }))" @update:modelValue="fillFromJamaah" />
        <AppInput v-model="distForm.recipient_name" label="Nama Penerima" required :error="distForm.errors.recipient_name" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Alamat</label>
          <textarea v-model="distForm.address" rows="2" class="input" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model.number="distForm.weight_kg" type="number" label="Berat (kg)" :error="distForm.errors.weight_kg" />
          <AppInput v-model.number="distForm.package_count" type="number" label="Jumlah Paket" required :error="distForm.errors.package_count" />
        </div>
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showDistribute = false">Batal</AppButton>
        <AppButton :loading="distForm.processing" @click="submitDistribute">Simpan</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Plus as PlusIcon, FileText as FileTextIcon } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  year: { type: Number, required: true },
  registrations: { type: Array, default: () => [] },
  distributions: { type: Array, default: () => [] },
  jamaahOptions: { type: Array, default: () => [] },
  summary: { type: Object, required: true },
})

const showAdd = ref(false)
const addForm = useForm({ shohibul_name: '', phone: '', animal_type: 'kambing', share_count: 1, animal_name: '', payment_status: 'pending' })

function submitAdd() {
  addForm.post(route('admin.ramadhan.qurban.store', { year: props.year }), {
    preserveScroll: true,
    onSuccess: () => { showAdd.value = false; addForm.reset() },
  })
}

function deleteRegistration(registration) {
  if (confirm(`Hapus pendaftaran ${registration.shohibul_name}?`)) {
    router.delete(route('admin.ramadhan.qurban.destroy', registration.id), { preserveScroll: true })
  }
}

const showDistribute = ref(false)
const distForm = useForm({ jamaah_id: null, recipient_name: '', address: '', weight_kg: '', package_count: 1 })

function fillFromJamaah(id) {
  const jamaah = props.jamaahOptions.find((j) => j.id === id)
  if (jamaah) {
    distForm.jamaah_id = jamaah.id
    distForm.recipient_name = jamaah.name
    distForm.address = jamaah.address
  }
}

function submitDistribute() {
  distForm.post(route('admin.ramadhan.qurban.distribusi.store', { year: props.year }), {
    preserveScroll: true,
    onSuccess: () => { showDistribute.value = false; distForm.reset() },
  })
}
</script>
