<template>
  <Head title="Superadmin - Daftar Tenant" />

  <PlatformAdminLayout>
    <h1 class="text-xl font-bold text-[var(--text-primary)] mb-4">Semua Tenant ({{ tenants.total }})</h1>

    <div class="card overflow-x-auto">
      <table class="table w-full text-sm">
        <thead>
          <tr class="text-left text-[var(--text-muted)]">
            <th class="p-3">Nama</th>
            <th class="p-3">Alamat Portal</th>
            <th class="p-3">Pengguna</th>
            <th class="p-3">Tarif/Bulan</th>
            <th class="p-3">Bulan Ini</th>
            <th class="p-3">Masa Aktif</th>
            <th class="p-3">Terdaftar</th>
            <th class="p-3">Status</th>
            <th class="p-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="tenant in tenants.data" :key="tenant.id" class="border-t border-[var(--border)]">
            <td class="p-3 font-medium text-[var(--text-primary)]">
              <Link :href="route('platform-admin.tenant.show', tenant.id)" class="hover:underline">{{ tenant.name }}</Link>
            </td>
            <td class="p-3">
              <a :href="tenant.subdomain_url" target="_blank" class="text-primary-600 hover:underline">{{ tenant.slug }}</a>
              <div v-if="tenant.custom_domain" class="text-xs text-[var(--text-muted)] mt-0.5">
                {{ tenant.custom_domain }}
                <span v-if="tenant.custom_domain_verified" class="text-green-600">(terverifikasi)</span>
                <span v-else class="text-amber-600">(belum diverifikasi)</span>
              </div>
            </td>
            <td class="p-3">{{ tenant.users_count }}</td>
            <td class="p-3">
              {{ formatRupiah(tenant.monthly_fee) }}
              <span v-if="tenant.has_custom_fee" class="text-xs text-amber-600 block">(khusus)</span>
              <Link :href="route('platform-admin.tenant.show', tenant.id)" class="text-xs text-primary-600 hover:underline block">Edit</Link>
            </td>
            <td class="p-3">
              <span :class="tenant.paid_this_month ? 'text-green-600' : 'text-red-500'" class="font-medium">
                {{ tenant.paid_this_month ? 'Lunas' : 'Belum bayar' }}
              </span>
            </td>
            <td class="p-3">
              <span v-if="!tenant.active_until" class="text-[var(--text-muted)]">-</span>
              <span v-else :class="tenant.is_expired ? 'text-red-500 font-medium' : ''">
                {{ tenant.active_until }}
                <span v-if="tenant.is_expired" class="block text-xs">Kedaluwarsa</span>
              </span>
              <Link :href="route('platform-admin.tenant.show', tenant.id)" class="text-xs text-primary-600 hover:underline block">Edit</Link>
            </td>
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
  </PlatformAdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout.vue'

defineProps({
  tenants: { type: Object, required: true },
})

function toggleActive(tenant) {
  if (!confirm(`${tenant.is_active ? 'Nonaktifkan' : 'Aktifkan'} tenant "${tenant.name}"?`)) return
  router.post(route('platform-admin.tenant.toggle-active', tenant.id), {}, { preserveScroll: true })
}

function formatRupiah(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value ?? 0)
}
</script>
