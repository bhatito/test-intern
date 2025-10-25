<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/stores/auth'

const auth = useAuth()
const route = useRoute()
const router = useRouter()

const mobileOpen = ref(false)

// (opsional) deteksi role—bisa dipakai kalau nanti ada menu khusus manager PPIC
const isManagerPPIC = computed(
  () => auth.user?.department === 'ppic' && auth.user?.role === 'managerppic'
)

const isActive = (path) => route.path === path

const goToDashboard = () => router.push('/ppic')

const logout = async () => {
  try {
    await axios.post('/api/logout')
  } catch (e) {
    console.error(e)
  } finally {
    auth.logout()
    router.push('/')
  }
}
</script>

<template>
  <div class="min-h-dvh bg-gray-50 flex flex-col">
    <!-- HEADER -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-30">
      <div class="max-w-7xl mx-auto flex justify-between items-center px-4 sm:px-6 py-4">
        <!-- Brand -->
        <div class="flex items-center gap-3">
          <h1
            class="text-lg sm:text-xl font-bold text-gray-800 cursor-pointer"
            @click="goToDashboard"
          >
            🏭 PPIC System
          </h1>

          <!-- Desktop Nav -->
          <nav class="hidden md:flex gap-5 ml-6 text-sm font-medium">
            <RouterLink
              to="/ppic"
              class="px-2 py-1 rounded transition"
              :class="isActive('/ppic') ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'"
            >Dashboard</RouterLink>

            <RouterLink
              to="/ppic/master-produk"
              class="px-2 py-1 rounded transition"
              :class="isActive('/ppic/master-produk') ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'"
            >Master Produk</RouterLink>

            <RouterLink
              to="/ppic/rencana-produksi"
              class="px-2 py-1 rounded transition"
              :class="isActive('/ppic/rencana-produksi') ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'"
            >Rencana Produksi</RouterLink>

            <RouterLink
              to="/ppic/laporan-produksi"
              class="px-2 py-1 rounded transition"
              :class="isActive('/ppic/laporan-produksi') ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'"
            >Laporan</RouterLink>

            <!-- contoh menu khusus manager ppic (opsional)
            <RouterLink
              v-if="isManagerPPIC"
              to="/ppic/rekap"
              class="px-2 py-1 rounded transition"
              :class="isActive('/ppic/rekap') ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'"
            >Rekap</RouterLink>
            -->
          </nav>
        </div>

        <!-- User info + actions -->
        <div class="flex items-center gap-3">
          <div class="hidden sm:block text-right">
            <p class="text-gray-700 text-sm font-medium">
              {{ auth.user?.name }}
            </p>
            <p class="text-gray-400 text-xs capitalize">
              {{ auth.user?.department }} — {{ auth.user?.role }}
            </p>
          </div>

          <!-- Mobile menu -->
          <button
            class="md:hidden p-2 rounded border hover:bg-gray-50"
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
            class="hidden sm:inline px-3 py-1.5 text-sm bg-red-500 text-white rounded hover:bg-red-600 transition"
          >
            Logout
          </button>
        </div>
      </div>

      <!-- Mobile Dropdown -->
      <div v-if="mobileOpen" class="md:hidden bg-gray-50 border-t">
        <nav class="flex flex-col px-4 py-3 gap-2 text-sm font-medium text-gray-700">
          <RouterLink
            to="/ppic"
            @click="mobileOpen = false"
            class="px-3 py-2 rounded"
            :class="isActive('/ppic') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >Dashboard</RouterLink>

          <RouterLink
            to="/ppic/master-produk"
            @click="mobileOpen = false"
            class="px-3 py-2 rounded"
            :class="isActive('/ppic/master-produk') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >Master Produk</RouterLink>

          <RouterLink
            to="/ppic/rencana-produksi"
            @click="mobileOpen = false"
            class="px-3 py-2 rounded"
            :class="isActive('/ppic/rencana-produksi') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >Rencana Produksi</RouterLink>

          <RouterLink
            to="/ppic/laporan-produksi"
            @click="mobileOpen = false"
            class="px-3 py-2 rounded"
            :class="isActive('/ppic/laporan-produksi') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >Laporan</RouterLink>

          <!-- opsional: menu khusus manager ppic
          <RouterLink
            v-if="isManagerPPIC"
            to="/ppic/rekap"
            @click="mobileOpen = false"
            class="px-3 py-2 rounded"
            :class="isActive('/ppic/rekap') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >Rekap</RouterLink>
          -->

          <button
            @click="logout"
            class="mt-2 text-left px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition"
          >
            Logout
          </button>
        </nav>
      </div>
    </header>

    <!-- BODY -->
    <div class="flex flex-1">
      <!-- SIDEBAR (desktop) -->
      <aside
        class="hidden md:block w-64 bg-white border-r border-gray-100 sticky top-[64px] self-start h-[calc(100dvh-64px)] overflow-y-auto"
      >
        <nav class="flex flex-col p-4 gap-2 text-sm">
          <RouterLink
            to="/ppic"
            class="px-4 py-2 rounded font-medium"
            :class="isActive('/ppic') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >🏠 Dashboard</RouterLink>

          <RouterLink
            to="/ppic/master-produk"
            class="px-4 py-2 rounded font-medium"
            :class="isActive('/ppic/master-produk') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >📦 Master Produk</RouterLink>

          <RouterLink
            to="/ppic/rencana-produksi"
            class="px-4 py-2 rounded font-medium"
            :class="isActive('/ppic/rencana-produksi') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >📝 Rencana Produksi</RouterLink>

          <RouterLink
            to="/ppic/laporan-produksi"
            class="px-4 py-2 rounded font-medium"
            :class="isActive('/ppic/laporan-produksi') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >📊 Laporan</RouterLink>

          <!-- opsional khusus manager ppic
          <RouterLink
            v-if="isManagerPPIC"
            to="/ppic/rekap"
            class="px-4 py-2 rounded font-medium"
            :class="isActive('/ppic/rekap') ? 'bg-gray-200 text-gray-900' : 'hover:bg-gray-100'"
          >🗂️ Rekap</RouterLink>
          -->
        </nav>
      </aside>

      <!-- KONTEN -->
      <main class="flex-1 p-4 sm:p-6">
        <slot />
      </main>
    </div>

    <!-- FOOTER -->
    <footer class="text-center text-gray-500 text-xs sm:text-sm py-4 border-t">
      &copy; {{ new Date().getFullYear() }} PPIC System — Lea Workspace
    </footer>
  </div>
</template>
