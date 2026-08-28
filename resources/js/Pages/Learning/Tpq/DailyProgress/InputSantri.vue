<template>
  <Head :title="`Mengaji Harian - ${student.name}`" />

  <AdminLayout title="Mengaji Harian">
    <div class="max-w-lg mx-auto">
      <Link :href="route('admin.tpq.daily-progress.index', { date })" class="inline-flex items-center gap-1 text-sm text-[var(--text-muted)] hover:text-[var(--text-primary)] mb-4">
        <ArrowLeftIcon class="w-4 h-4" /> Scan/cari santri lain
      </Link>

      <AppCard>
        <div class="flex items-center gap-3 mb-1">
          <div class="w-14 h-14 rounded-full bg-[var(--bg-muted)] flex items-center justify-center text-2xl overflow-hidden shrink-0">
            <img v-if="student.photo" :src="student.photo" class="w-full h-full object-cover" alt="" />
            <span v-else>🧒</span>
          </div>
          <div class="min-w-0">
            <p class="font-semibold text-lg text-[var(--text-primary)] truncate">{{ student.name }}</p>
            <p class="text-sm text-[var(--text-muted)]">{{ student.nis }} · {{ student.class ?? 'Belum ada kelas' }}</p>
          </div>
        </div>
        <p class="text-xs text-[var(--text-muted)] mb-3">{{ formatDate(date) }}</p>

        <div class="flex items-center justify-between gap-2 bg-[var(--bg-muted)] rounded-lg px-3 py-2 mb-5">
          <div>
            <p class="text-[10px] uppercase tracking-wide text-[var(--text-muted)]">Jenjang Saat Ini</p>
            <p class="text-sm font-semibold text-[var(--text-primary)]">{{ student.level_label }}</p>
          </div>
          <button
            v-if="student.next_level_label"
            type="button"
            class="text-xs font-medium text-primary-600 hover:underline whitespace-nowrap"
            @click="confirmPromote"
          >
            Naik ke {{ student.next_level_label }} →
          </button>
        </div>

        <details v-if="student.recent_promotions?.length" class="mb-5 -mt-3">
          <summary class="text-xs text-[var(--text-muted)] cursor-pointer hover:underline">Riwayat kenaikan jenjang</summary>
          <ul class="mt-2 space-y-1">
            <li v-for="(p, i) in student.recent_promotions" :key="i" class="text-xs text-[var(--text-muted)]">
              {{ p.from_label }} → {{ p.to_label }} · {{ formatDate(p.date) }}<span v-if="p.promoted_by"> · {{ p.promoted_by }}</span>
            </li>
          </ul>
        </details>

        <div v-if="student.filled" class="text-xs font-medium text-green-600 bg-green-50 dark:bg-green-900/20 rounded-lg px-3 py-2 mb-4">
          Sudah diisi hari ini{{ student.today_recorded_by ? ` oleh Ust. ${student.today_recorded_by}` : '' }} — simpan lagi untuk memperbarui (tidak akan kirim notifikasi dobel ke wali).
        </div>

        <div v-if="student.recent_history?.length" class="mb-5">
          <p class="text-xs font-medium text-[var(--text-muted)] mb-2">Riwayat Mengaji Terakhir</p>
          <div class="space-y-2 max-h-52 overflow-y-auto border border-[var(--border)] rounded-lg p-3">
            <div v-for="(h, i) in student.recent_history" :key="i" class="text-xs border-b border-[var(--border)] last:border-0 pb-2 last:pb-0">
              <div class="flex items-center justify-between">
                <span class="font-medium text-[var(--text-primary)]">{{ formatDate(h.date) }}</span>
                <span
                  class="px-2 py-0.5 rounded-full font-medium"
                  :class="h.keterangan === 'lancar' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300'"
                >
                  {{ h.keterangan === 'lancar' ? 'Lancar' : 'Ulang' }}
                </span>
              </div>
              <p class="text-[var(--text-muted)] mt-0.5">{{ h.summary }}</p>
              <p v-if="h.catatan" class="italic text-[var(--text-muted)] mt-0.5">"{{ h.catatan }}"</p>
              <p v-if="h.recorded_by" class="text-[var(--text-muted)] mt-0.5">Ust. {{ h.recorded_by }}</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2 mb-4">
          <button
            type="button"
            class="py-2.5 rounded-lg text-sm font-medium transition-colors"
            :class="form.method === 'iqro' ? 'bg-primary-600 text-white' : 'bg-[var(--bg-muted)] text-[var(--text-muted)]'"
            @click="form.method = 'iqro'"
          >
            Iqro
          </button>
          <button
            type="button"
            class="py-2.5 rounded-lg text-sm font-medium transition-colors"
            :class="form.method === 'quran' ? 'bg-primary-600 text-white' : 'bg-[var(--bg-muted)] text-[var(--text-muted)]'"
            @click="form.method = 'quran'"
          >
            Al-Qur'an
          </button>
        </div>

        <div v-if="form.method === 'iqro'" class="grid grid-cols-2 gap-3 mb-4">
          <div>
            <label class="block text-xs text-[var(--text-muted)] mb-1">Jilid</label>
            <select v-model.number="form.jilid" class="input">
              <option :value="null" disabled>Pilih jilid</option>
              <option v-for="n in 6" :key="n" :value="n">Jilid {{ n }}</option>
            </select>
            <p v-if="form.errors.jilid" class="mt-1 text-xs text-red-500">{{ form.errors.jilid }}</p>
          </div>
          <div>
            <label class="block text-xs text-[var(--text-muted)] mb-1">Halaman</label>
            <input v-model.number="form.halaman" type="number" min="1" class="input" />
          </div>
        </div>

        <div v-else class="mb-4">
          <label class="block text-xs text-[var(--text-muted)] mb-1">Surat</label>
          <input v-model="form.surah" type="text" placeholder="Al-Baqarah" class="input mb-3" />
          <p v-if="form.errors.surah" class="mt-1 mb-2 text-xs text-red-500">{{ form.errors.surah }}</p>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-[var(--text-muted)] mb-1">Ayat Awal</label>
              <input v-model.number="form.ayat_awal" type="number" min="1" class="input" />
            </div>
            <div>
              <label class="block text-xs text-[var(--text-muted)] mb-1">Ayat Akhir</label>
              <input v-model.number="form.ayat_akhir" type="number" min="1" class="input" />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2 mb-4">
          <button
            type="button"
            class="py-2.5 rounded-lg text-sm font-medium transition-colors"
            :class="form.keterangan === 'lancar' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' : 'bg-[var(--bg-muted)] text-[var(--text-muted)]'"
            @click="form.keterangan = 'lancar'"
          >
            Lancar
          </button>
          <button
            type="button"
            class="py-2.5 rounded-lg text-sm font-medium transition-colors"
            :class="form.keterangan === 'ulang' ? 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300' : 'bg-[var(--bg-muted)] text-[var(--text-muted)]'"
            @click="form.keterangan = 'ulang'"
          >
            Ulang
          </button>
        </div>

        <input v-model="form.catatan" type="text" placeholder="Catatan (opsional)..." class="input mb-5" />

        <AppButton class="w-full justify-center" :loading="form.processing" @click="save">
          Simpan & Lanjut Santri Berikutnya
        </AppButton>
      </AppCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft as ArrowLeftIcon } from 'lucide-vue-next'
import dayjs from 'dayjs'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AppCard from '@/Components/UI/AppCard.vue'
import AppButton from '@/Components/UI/AppButton.vue'

const props = defineProps({
  date: { type: String, required: true },
  student: { type: Object, required: true },
})

const form = useForm({
  date: props.date,
  class_id: props.student.class_id,
  method: props.student.method,
  jilid: props.student.jilid,
  halaman: props.student.halaman,
  surah: props.student.surah,
  ayat_awal: props.student.ayat_awal,
  ayat_akhir: props.student.ayat_akhir,
  keterangan: props.student.keterangan,
  catatan: props.student.catatan,
})

function save() {
  form.post(route('admin.tpq.daily-progress.santri.store', props.student.id), {
    preserveScroll: true,
    onSuccess: () => {
      router.visit(route('admin.tpq.daily-progress.index', { date: props.date }))
    },
  })
}

function confirmPromote() {
  if (!confirm(`Naikkan jenjang ${props.student.name} ke ${props.student.next_level_label}?`)) return

  router.post(route('admin.tpq.daily-progress.santri.promote', props.student.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      // Props sudah ter-refresh dengan jenjang baru — sinkronkan form input
      // supaya kalau belum diisi hari ini, langsung ikut jenjang baru tanpa
      // guru perlu ganti manual di dropdown.
      if (!props.student.filled) {
        form.method = props.student.method
        form.jilid = props.student.jilid
        form.halaman = null
      }
    },
  })
}

function formatDate(value) {
  return dayjs(value).format('dddd, DD MMMM YYYY')
}
</script>
