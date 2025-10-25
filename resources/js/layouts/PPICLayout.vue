<script setup>
import { ref } from 'vue'
import { useAuth } from '@/stores/auth'
import { useRouter, RouterLink } from 'vue-router'
import axios from 'axios'

const auth = useAuth()
const router = useRouter()
const mobileMenu = ref(false)

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

const goToDashboard = () => {
  router.push('/ppic')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- HEADER -->
    <header class="bg-white shadow-sm border-b">
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
          <nav class="hidden md:flex gap-5 ml-6 text-sm font-medium text-gray-600">
            <RouterLink to="/ppic" class="hover:text-black transition">Dashboard</RouterLink>
            <RouterLink to="/ppic/master-produk" class="hover:text-black transition">Master Produk</RouterLink>
            <RouterLink to="/ppic/rencana-produksi" class="hover:text-black transition">Rencana</RouterLink>
            <RouterLink to="/ppic/laporan-produksi" class="hover:text-black transition">Laporan</RouterLink>
          </nav>
        </div>

        <!-- User info + mobile menu button -->
        <div class="flex items-center gap-3">
          <div class="hidden sm:block text-right">
            <p class="text-gray-700 text-sm font-medium">
              {{ auth.user?.name }}
              <span class="text-gray-400 text-xs block sm:inline">({{ auth.user?.department }})</span>
            </p>
          </div>

          <button
            @click="logout"
            class="hidden sm:inline px-3 py-1.5 text-sm bg-red-500 text-white rounded hover:bg-red-600 transition"
          >
            Logout
          </button>

          <!-- Mobile Menu Button -->
          <button
            @click="mobileMenu = !mobileMenu"
            class="md:hidden p-2 rounded hover:bg-gray-100 border"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
              stroke-width="2" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile Dropdown -->
      <div v-if="mobileMenu" class="md:hidden bg-gray-50 border-t">
        <nav class="flex flex-col px-4 py-3 gap-2 text-sm font-medium text-gray-700">
          <RouterLink to="/ppic" @click="mobileMenu = false" class="hover:text-black">Dashboard</RouterLink>
          <RouterLink to="/ppic/master-produk" @click="mobileMenu = false" class="hover:text-black">Master Produk</RouterLink>
          <RouterLink to="/ppic/rencana-produksi" class="hover:text-black transition">Rencana Produksi</RouterLink>
          <RouterLink to="/ppic/laporan-produksi" @click="mobileMenu = false" class="hover:text-black">Laporan</RouterLink>
          <button
            @click="logout"
            class="mt-2 text-left px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition"
          >
            Logout
          </button>
        </nav>
      </div>
    </header>

    <!-- MAIN -->
    <main class="flex-1 max-w-7xl mx-auto w-full p-4 sm:p-6">
      <slot />
    </main>

    <!-- FOOTER -->
    <footer class="text-center text-gray-500 text-xs sm:text-sm py-4 border-t">
      &copy; {{ new Date().getFullYear() }} PPIC System — Lea Workspace
    </footer>
  </div>
</template>
