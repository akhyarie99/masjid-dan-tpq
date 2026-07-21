<template>
  <Head :title="isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'" />

  <AdminLayout :title="isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'">
    <PageHeader :title="isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'" />

    <AppCard class="max-w-xl">
      <form class="space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.name" label="Nama Lengkap" required :error="form.errors.name" />
        <AppInput v-model="form.phone" label="Nomor HP" required :error="form.errors.phone" />
        <AppInput v-model="form.email" type="email" label="Email" :error="form.errors.email" />
        <AppInput
          v-model="form.password"
          type="password"
          :label="isEdit ? 'Kata Sandi Baru (opsional)' : 'Kata Sandi'"
          :required="!isEdit"
          :error="form.errors.password"
        />
        <AppSelect
          v-model="form.role"
          label="Peran"
          required
          :options="roles.map((role) => ({ label: role, value: role }))"
          :error="form.errors.role"
        />
        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
          <input v-model="form.is_active" type="checkbox" class="rounded border-[var(--border)] text-primary-600 focus:ring-primary-500" />
          Akun aktif
        </label>

        <div class="flex justify-end gap-2 pt-2">
          <Link :href="route('admin.settings.pengguna.index')" class="btn-secondary">Batal</Link>
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
  user: { type: Object, default: null },
  roles: { type: Array, default: () => [] },
})

const isEdit = computed(() => props.user !== null)

const form = useForm({
  name: props.user?.name ?? '',
  phone: props.user?.phone ?? '',
  email: props.user?.email ?? '',
  password: '',
  role: props.user?.roles?.[0]?.name ?? '',
  is_active: props.user?.is_active ?? true,
})

function submit() {
  if (isEdit.value) {
    form.put(route('admin.settings.pengguna.update', props.user.id))
  } else {
    form.post(route('admin.settings.pengguna.store'))
  }
}
</script>
