<script setup>
import axios from 'axios'
import { useAuth } from '@/stores/auth'

const auth = useAuth()

// Fungsi logout
const logout = async () => {
  try {
    await axios.post('/api/logout')
  } catch (e) {
    console.error(e)
  } finally {
    // Bersihkan data lokal
    auth.logout()
    // Redirect ke halaman utama / pilih login
    window.location.assign('/')
  }
}
</script>

<template>
  <div class="min-h-dvh bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="flex items-center justify-between bg-white shadow px-6 py-4">
      <h1 class="text-xl font-bold text-gray-800">Dashboard PRODUKSI</h1>

      <div class="flex items-center gap-4">
        <p class="text-gray-700 text-sm font-medium">
          {{ auth.user?.name }}
          <span class="text-gray-400 text-xs">({{ auth.user?.department }})</span>
        </p>
        <button
          @click="logout"
          class="px-4 py-1.5 rounded-lg bg-red-500 text-white text-sm hover:bg-red-600 transition"
        >
          Logout
        </button>
      </div>
    </header>

    <!-- Konten utama -->
    <main class="flex-1 p-6">
      <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Selamat datang di Dashboard PRODUKSI 👋</h2>
        <p class="text-gray-600">Anda berhasil login sebagai {{ auth.user?.role }}.</p>
      </div>
    </main>
  </div>
</template>
