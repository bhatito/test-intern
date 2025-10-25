<script setup>
import { useAuth } from '@/stores/auth'
import axios from 'axios'
import { RouterLink } from 'vue-router'

const auth = useAuth()

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
    <header class="flex items-center justify-between bg-white shadow px-6 py-4">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800">
        Dashboard Produksi
      </h1>

      <div class="flex items-center gap-4">
        <div class="text-right">
          <p class="text-gray-700 text-sm font-medium">
            {{ auth.user?.name }}
          </p>
          <p class="text-gray-400 text-xs capitalize">
            {{ auth.user?.department }} — {{ auth.user?.role }}
          </p>
        </div>

        <button
          @click="logout"
          class="px-4 py-1.5 rounded-lg bg-red-500 text-white text-sm hover:bg-red-600 transition"
        >
          Logout
        </button>
      </div>
    </header>

    <!-- MAIN CONTENT -->
    <div class="flex flex-1 flex-col sm:flex-row">
      <!-- SIDEBAR -->
      <aside class="bg-white sm:w-64 shadow-inner border-r border-gray-100">
        <nav class="flex flex-col p-4 gap-2 text-sm">
          <RouterLink
            to="/produksi"
            class="px-4 py-2 rounded hover:bg-gray-100 transition font-medium"
            active-class="bg-gray-200 text-gray-900 font-semibold"
          >
            🏠 Dashboard
          </RouterLink>

          <RouterLink
            to="/produksi/order-produksi"
            class="px-4 py-2 rounded hover:bg-gray-100 transition font-medium"
            active-class="bg-gray-200 text-gray-900 font-semibold"
          >
            📦 Order Produksi
          </RouterLink>

          <RouterLink
            to="/produksi/laporan-produksi"
            class="px-4 py-2 rounded hover:bg-gray-100 transition font-medium"
            active-class="bg-gray-200 text-gray-900 font-semibold"
          >
            📊 Laporan Produksi
          </RouterLink>
          <RouterLink
            to="/produksi/persetujuan"
            class="px-4 py-2 rounded hover:bg-gray-100 transition font-medium"
            active-class="bg-gray-200 text-gray-900 font-semibold"
            >
            ✅ Persetujuan Rencana
          </RouterLink>

        </nav>
      </aside>

      <!-- KONTEN HALAMAN -->
      <main class="flex-1 p-6 overflow-x-auto">
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
