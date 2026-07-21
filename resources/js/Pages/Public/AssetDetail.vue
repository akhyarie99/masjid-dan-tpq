<template>
  <Head :title="asset.name" />

  <PublicLayout>
    <div class="max-w-md mx-auto px-4 py-10 md:py-14">
      <div class="card p-6 text-center">
        <div class="text-4xl mb-3">📦</div>
        <h1 class="text-xl font-bold text-[var(--text-primary)]">{{ asset.name }}</h1>
        <p class="text-sm text-[var(--text-muted)] font-mono">{{ asset.asset_code }}</p>

        <dl class="mt-6 divide-y divide-[var(--border)] text-left">
          <div class="py-2 flex justify-between text-sm"><dt class="text-[var(--text-muted)]">Kategori</dt><dd>{{ asset.category?.name }}</dd></div>
          <div class="py-2 flex justify-between text-sm"><dt class="text-[var(--text-muted)]">Lokasi</dt><dd>{{ asset.location }}</dd></div>
          <div class="py-2 flex justify-between text-sm"><dt class="text-[var(--text-muted)]">Kondisi</dt><dd class="capitalize">{{ asset.condition?.replace('_', ' ') }}</dd></div>
          <div class="py-2 flex justify-between text-sm"><dt class="text-[var(--text-muted)]">Status</dt><dd class="capitalize">{{ asset.status }}</dd></div>
        </dl>

        <p class="text-xs text-[var(--text-muted)] mt-6">Aset milik {{ masjidName }}</p>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

defineProps({
  asset: { type: Object, required: true },
  masjidName: { type: String, required: true },
})
</script>
