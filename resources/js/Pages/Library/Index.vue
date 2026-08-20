<template>
  <Head title="Perpustakaan" />

  <AdminLayout title="Perpustakaan">
    <PageHeader title="Perpustakaan" description="Katalog buku masjid.">
      <template #actions>
        <Link :href="route('admin.library.loans.index')" class="btn-secondary">Peminjaman</Link>
        <AppButton v-if="can('library.manage')" @click="openCreate"><PlusIcon class="w-4 h-4" /> Tambah Buku</AppButton>
      </template>
    </PageHeader>

    <AppCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <AppInput v-model="filters.search" placeholder="Cari judul/penulis..." />
        <AppSelect v-model="filters.category" placeholder="Semua Kategori" :options="categories.map((c) => ({ label: c, value: c }))" />
      </div>
    </AppCard>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <EmptyState v-if="books.data.length === 0" title="Belum ada buku" class="col-span-full" />
      <div v-for="book in books.data" :key="book.id" class="card p-4">
        <div class="h-32 rounded-lg bg-[var(--bg-muted)] flex items-center justify-center mb-3 text-3xl overflow-hidden">
          <img v-if="book.cover_path" :src="`/storage/${book.cover_path}`" class="w-full h-full object-cover" alt="" />
          <span v-else>📚</span>
        </div>
        <p class="font-medium text-[var(--text-primary)] truncate">{{ book.title }}</p>
        <p class="text-xs text-[var(--text-muted)] truncate">{{ book.author ?? '-' }}</p>
        <p class="text-xs text-[var(--text-muted)] mt-1">Stok: {{ book.stock - book.active_loans_count }}/{{ book.stock }}</p>
        <button v-if="can('library.manage')" class="text-primary-600 text-sm hover:underline mt-2" @click="openEdit(book)">Edit</button>
      </div>
    </div>

    <div class="mt-4">
      <AppPagination :links="books.links" />
    </div>

    <AppModal :show="showModal" :title="editing ? 'Edit Buku' : 'Tambah Buku'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.title" label="Judul Buku" required :error="form.errors.title" />
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.author" label="Penulis" :error="form.errors.author" />
          <AppInput v-model="form.publisher" label="Penerbit" :error="form.errors.publisher" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.category" label="Kategori" :error="form.errors.category" />
          <AppInput v-model.number="form.stock" type="number" label="Stok" required :error="form.errors.stock" />
        </div>
        <AppInput v-model="form.isbn" label="ISBN (opsional)" :error="form.errors.isbn" />
        <div>
          <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">Deskripsi</label>
          <textarea v-model="form.description" rows="2" class="input" />
        </div>
      </form>
      <template #footer>
        <AppButton variant="secondary" @click="showModal = false">Batal</AppButton>
        <AppButton :loading="form.processing" @click="submit">Simpan</AppButton>
      </template>
    </AppModal>
  </AdminLayout>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { Plus as PlusIcon } from 'lucide-vue-next'
import { usePermission } from '@/composables/usePermission'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppInput from '@/Components/UI/AppInput.vue'
import AppSelect from '@/Components/UI/AppSelect.vue'
import AppButton from '@/Components/UI/AppButton.vue'
import AppModal from '@/Components/UI/AppModal.vue'
import AppPagination from '@/Components/UI/AppPagination.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

const props = defineProps({
  books: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
})

const { can } = usePermission()

const filters = reactive({ search: props.filters.search ?? '', category: props.filters.category ?? '' })

watch(filters, () => {
  router.get(route('admin.library.index'), { ...filters }, { preserveState: true, replace: true })
}, { deep: true })

const showModal = ref(false)
const editing = ref(null)
const form = useForm({ title: '', author: '', publisher: '', category: '', isbn: '', stock: 1, description: '' })

function openCreate() {
  editing.value = null
  form.reset()
  showModal.value = true
}

function openEdit(book) {
  editing.value = book
  form.title = book.title
  form.author = book.author
  form.publisher = book.publisher
  form.category = book.category
  form.isbn = book.isbn
  form.stock = book.stock
  form.description = book.description
  showModal.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.library.update', editing.value.id), options)
  } else {
    form.post(route('admin.library.store'), options)
  }
}
</script>
