<template>
  <Head :title="program.name" />

  <AdminLayout :title="program.name">
    <PageHeader :title="program.name" :description="`Anggaran: ${formatCurrency(program.budget)}`">
      <template #actions>
        <a :href="route('admin.jamaah.program-sosial.report', program.id)" class="btn-secondary"><FileTextIcon class="w-4 h-4" /> Laporan Distribusi</a>
        <AppButton @click="showAdd = true"><PlusIcon class="w-4 h-4" /> Tambah Penerima</AppButton>
      </template>
    </PageHeader>

    <AppCard :padded="false">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Nama</th><th>Alamat</th><th>Jenis Bantuan</th><th>Nominal</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <tr v-if="recipients.length === 0">
              <td colspan="6" class="text-center text-[var(--text-muted)] py-8">Belum ada penerima.</td>
            </tr>
            <tr v-for="recipient in recipients" :key="recipient.id">
              <td>{{ recipient.name }}</td>
              <td class="max-w-[200px] truncate">{{ recipient.address ?? '-' }}</td>
              <td>{{ recipient.aid_type ?? '-' }}</td>
              <td>{{ recipient.amount ? formatCurrency(recipient.amount) : '-' }}</td>
              <td><AppBadge :variant="recipient.distributed_at ? 'green' : 'gray'">{{ recipient.distributed_at ? 'Terdistribusi' : 'Belum' }}</AppBadge></td>
              <td>
                <div class="flex items-center gap-2">
                  <button v-if="!recipient.distributed_at" class="text-primary-600 text-sm hover:underline" @click="openDistribute(recipient)">Distribusikan</button>
                  <a v-else :href="route('admin.jamaah.program-sosial.receipt', [program.id, recipient.id])" class="text-primary-600 text-sm hover:underline">Tanda Terima</a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <AppModal :show="showAdd" title="Tambah Penerima" @close="showAdd = false">
      <form class="space-y-4" @submit.prevent="addRecipient">
        <AppSelect v-model="selectedJamaahId" label="Pilih dari Database Jamaah (opsional)" placeholder="Atau isi manual di bawah" :options="jamaahOptions.map((j) => ({ label: j.name, value: j.id }))" @update:modelValue="fillFromJamaah" />
        <AppInput v-model="addForm.name" label="Nama" required :error="addForm.errors.name" />
        <AppInput v-model="addForm.phone" label="No. HP" :error="addForm.errors.phone" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Alamat</label>
          <textarea v-model="addForm.address" rows="2" class="input" />
        </div>
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showAdd = false">Batal</AppButton>
        <AppButton :loading="addForm.processing" @click="addRecipient">Tambah</AppButton>
      </template>
    </AppModal>

    <AppModal :show="distributeTarget !== null" title="Distribusikan Bantuan" @close="distributeTarget = null">
      <form v-if="distributeTarget" class="space-y-4" @submit.prevent="distribute">
        <p class="text-sm text-[var(--text-muted)]">Kepada: {{ distributeTarget.name }}</p>
        <AppInput v-model="distributeForm.aid_type" label="Jenis Bantuan" required :error="distributeForm.errors.aid_type" />
        <AppInput v-model.number="distributeForm.amount" type="number" label="Jumlah/Nominal (opsional)" :error="distributeForm.errors.amount" />
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="distributeTarget = null">Batal</AppButton>
        <AppButton :loading="distributeForm.processing" @click="distribute">Simpan</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
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
  program: { type: Object, required: true },
  recipients: { type: Array, default: () => [] },
  jamaahOptions: { type: Array, default: () => [] },
})

const showAdd = ref(false)
const selectedJamaahId = ref('')
const addForm = useForm({ jamaah_id: null, name: '', phone: '', address: '' })

function fillFromJamaah(id) {
  const jamaah = props.jamaahOptions.find((j) => j.id === id)
  if (jamaah) {
    addForm.jamaah_id = jamaah.id
    addForm.name = jamaah.name
    addForm.phone = jamaah.phone
    addForm.address = jamaah.address
  }
}

function addRecipient() {
  addForm.post(route('admin.jamaah.program-sosial.penerima.store', props.program.id), {
    preserveScroll: true,
    onSuccess: () => { showAdd.value = false; addForm.reset(); selectedJamaahId.value = '' },
  })
}

const distributeTarget = ref(null)
const distributeForm = useForm({ aid_type: '', amount: '' })

function openDistribute(recipient) {
  distributeTarget.value = recipient
  distributeForm.reset()
}

function distribute() {
  distributeForm.post(route('admin.jamaah.program-sosial.distribusi', [props.program.id, distributeTarget.value.id]), {
    preserveScroll: true,
    onSuccess: () => { distributeTarget.value = null },
  })
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}
</script>
