<template>
  <div class="min-h-screen flex">
    <SidebarNav />

    <div class="flex-1 flex flex-col min-w-0">
      <TopBar :title="title" @toggle-mobile-menu="mobileMenuOpen = true" />

      <main class="flex-1 p-4 md:p-6 overflow-auto pb-20 md:pb-6">
        <slot />
      </main>
    </div>

    <MobileBottomNav />

    <TransitionRoot appear :show="mobileMenuOpen" as="template">
      <Dialog as="div" class="relative z-50 md:hidden" @close="mobileMenuOpen = false">
        <TransitionChild as="template" enter="duration-150 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-100 ease-in" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-black/50" />
        </TransitionChild>
        <div class="fixed inset-0 flex">
          <TransitionChild as="template" enter="duration-150 ease-out" enter-from="-translate-x-full" enter-to="translate-x-0" leave="duration-100 ease-in" leave-from="translate-x-0" leave-to="-translate-x-full">
            <DialogPanel class="w-[280px] h-full">
              <SidebarNav mobile />
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>

    <AppToast />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import SidebarNav from '@/Components/Shared/SidebarNav.vue'
import TopBar from '@/Components/Shared/TopBar.vue'
import MobileBottomNav from '@/Components/Shared/MobileBottomNav.vue'
import AppToast from '@/Components/UI/AppToast.vue'

defineProps({
  title: { type: String, default: '' },
})

const mobileMenuOpen = ref(false)
</script>
