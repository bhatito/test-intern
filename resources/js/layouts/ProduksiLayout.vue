<script setup>
import { ref, computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/stores/auth'

const auth = useAuth()
const route = useRoute()
const mobileOpen = ref(false)

// Tampilkan menu Persetujuan hanya utk manager produksi
const isManagerProduksi = computed(() =>
  auth.user?.department === 'produksi' && auth.user?.role === 'managerpproduksi'
)

// helper active route
const isActive = (path) => route.path === path

const logout = async () => {
  try {
    await axios.post('/api/logout')
  } catch (e) {
    console.error(e)
  } finally {
    auth.logout()
    window.location.assign('/')
  }
}
</script>

<template>
  <div class="min-h-dvh bg-gray-50 flex flex-col">
    <!-- HEADER -->
    <header class="bg-white shadow px-4 sm:px-6 py-3 sticky top-0 z-30">
      <div class="flex items-center justify-between">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Dashboard Produksi</h1>

        <div class="flex items-center gap-3">
          <div class="hidden sm:block text-right">
            <p class="text-gray-700 text-sm font-medium">{{ auth.user?.name }}</p>
            <p class="text-gray-400 text-xs capitalize">
              {{ auth.user?.department }} — {{ auth.user?.role }}
            </p>
          </div>

          <button
            class="sm:hidden p-2 rounded border hover:bg-gray-50"
            @click="mobileOpen = !mobileOpen"
            aria-label="Toggle menu"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <button
            @click="logout"
            class="hidden sm:inline px-3 py-1.5 rounded bg-red-500 text-white text-sm hover:bg-red-600 transition"
          >
            Logout
          </button>
        </div>
      </div>

      <!-- Mobile dropdown -->
      <div v-if="mobileOpen" class="sm:hidden mt-3 border-t pt-3 space-y-2">
        <RouterLink
          to="/produksi"
          @click="mobileOpen = false"
          class="block px-3 py-2 rounded font-medium"
          :class="isActive('/produksi') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
        >🏠 Dashboard</RouterLink>

        <RouterLink
          to="/produksi/order-produksi"
          @click="mobileOpen = false"
          class="block px-3 py-2 rounded font-medium"
          :class="isActive('/produksi/order-produksi') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
        >📦 Order Produksi</RouterLink>

        <RouterLink
          to="/produksi/laporan-produksi"
          @click="mobileOpen = false"
          class="block px-3 py-2 rounded font-medium"
          :class="isActive('/produksi/laporan-produksi') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
        >📊 Laporan Produksi</RouterLink>

        <RouterLink
          v-if="isManagerProduksi"
          to="/produksi/persetujuan"
          @click="mobileOpen = false"
          class="block px-3 py-2 rounded font-medium"
          :class="isActive('/produksi/persetujuan') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
        >✅ Persetujuan Rencana</RouterLink>

        <button
          @click="logout"
          class="w-full text-left px-3 py-2 rounded bg-red-500 text-white"
        >
          Logout
        </button>
      </div>
    </header>

    <!-- BODY -->
    <div class="flex flex-1">
      <!-- SIDEBAR (desktop) -->
      <aside class="hidden sm:block w-64 bg-white border-r border-gray-100 sticky top-[60px] self-start h-[calc(100dvh-60px)] overflow-y-auto">
        <nav class="flex flex-col p-4 gap-2 text-sm">
          <RouterLink
            to="/produksi"
            class="px-4 py-2 rounded font-medium"
            :class="isActive('/produksi') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >🏠 Dashboard</RouterLink>

          <RouterLink
            to="/produksi/order-produksi"
            class="px-4 py-2 rounded font-medium"
            :class="isActive('/produksi/order-produksi') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >📦 Order Produksi</RouterLink>

          <RouterLink
            to="/produksi/laporan-produksi"
            class="px-4 py-2 rounded font-medium"
            :class="isActive('/produksi/laporan-produksi') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >📊 Laporan Produksi</RouterLink>

          <RouterLink
            v-if="isManagerProduksi"
            to="/produksi/persetujuan"
            class="px-4 py-2 rounded font-medium"
            :class="isActive('/produksi/persetujuan') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >✅ Persetujuan Rencana</RouterLink>
        </nav>
      </aside>

      <!-- KONTEN -->
      <main class="flex-1 p-4 sm:p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
@media (max-width: 640px) {
  header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>
