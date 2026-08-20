<template>
  <Head title="Superadmin - Daftar Tenant" />

  <div class="min-h-screen bg-[var(--bg-base)]">
    <header class="flex items-center justify-between p-4 md:p-6 max-w-6xl mx-auto">
      <div class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold">🛠️</div>
        <span class="font-semibold text-[var(--text-primary)]">Superadmin Platform</span>
      </div>
      <form @submit.prevent="logout">
        <button type="submit" class="btn-secondary">Keluar</button>
      </form>
    </header>

    <main class="max-w-6xl mx-auto px-4 md:px-6 pb-12">
      <AppAlert v-if="$page.props.flash?.success" variant="success" class="mb-4">{{ $page.props.flash.success }}</AppAlert>

      <h1 class="text-xl font-bold text-[var(--text-primary)] mb-4">Semua Tenant ({{ tenants.total }})</h1>

      <div class="card overflow-x-auto">
        <table class="table w-full text-sm">
          <thead>
            <tr class="text-left text-[var(--text-muted)]">
              <th class="p-3">Nama</th>
              <th class="p-3">Alamat Portal</th>
              <th class="p-3">Pengguna</th>
              <th class="p-3">Status Langganan</th>
              <th class="p-3">Terdaftar</th>
              <th class="p-3">Status</th>
              <th class="p-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tenant in tenants.data" :key="tenant.id" class="border-t border-[var(--border)]">
              <td class="p-3 font-medium text-[var(--text-primary)]">{{ tenant.name }}</td>
              <td class="p-3">
                <a :href="tenant.subdomain_url" target="_blank" class="text-primary-600 hover:underline">{{ tenant.slug }}</a>
                <div v-if="tenant.custom_domain" class="text-xs text-[var(--text-muted)] mt-0.5">
                  {{ tenant.custom_domain }}
                  <span v-if="tenant.custom_domain_verified" class="text-green-600">(terverifikasi)</span>
                  <span v-else class="text-amber-600">(belum diverifikasi)</span>
                </div>
              </td>
              <td class="p-3">{{ tenant.users_count }}</td>
              <td class="p-3 capitalize">{{ tenant.subscription_status }}</td>
              <td class="p-3">{{ tenant.created_at }}</td>
              <td class="p-3">
                <span :class="tenant.is_active ? 'text-green-600' : 'text-red-500'" class="font-medium">
                  {{ tenant.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="p-3">
                <button type="button" class="btn-secondary text-xs" @click="toggleActive(tenant)">
                  {{ tenant.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="tenants.links?.length > 3" class="flex flex-wrap gap-1 mt-4">
        <Link
          v-for="(link, i) in tenants.links"
          :key="i"
          :href="link.url || '#'"
          v-html="link.label"
          class="px-3 py-1 rounded text-sm"
          :class="link.active ? 'bg-primary-600 text-white' : 'text-[var(--text-muted)] hover:bg-[var(--bg-muted)]'"
        />
      </div>
    </main>
  </div>
</template>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppAlert from '@/Components/UI/AppAlert.vue'

defineProps({
  tenants: { type: Object, required: true },
})

function toggleActive(tenant) {
  if (!confirm(`${tenant.is_active ? 'Nonaktifkan' : 'Aktifkan'} tenant "${tenant.name}"?`)) return
  router.post(route('platform-admin.tenant.toggle-active', tenant.id), {}, { preserveScroll: true })
}

function logout() {
  useForm({}).post(route('platform-admin.logout'))
}
</script>
