<script setup>
import PPICLayout from '@/layouts/PPICLayout.vue'
import { useAuth } from '@/stores/auth'

const auth = useAuth()
</script>

<template>
  <PPICLayout>
    <div class="bg-white rounded-2xl shadow p-6">
      <h2 class="text-lg font-semibold mb-2">Selamat datang di Dashboard PPIC 👋</h2>
      <p class="text-gray-600 mb-6">
        Anda login sebagai <b>{{ auth.user?.role || '-' }}</b>.
      </p>

      <div class="grid md:grid-cols-3 gap-6">
        <RouterLink
          to="/ppic/master-produk"
          class="bg-blue-50 border border-blue-200 rounded-xl p-6 hover:bg-blue-100 transition"
        >
          <h3 class="font-semibold text-blue-700">Master Produk</h3>
          <p class="text-sm text-gray-600 mt-1">Kelola data produk perusahaan</p>
        </RouterLink>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 opacity-70">
          <h3 class="font-semibold text-gray-700">Rencana Produksi</h3>
          <p class="text-sm text-gray-600 mt-1">Fitur akan datang</p>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 opacity-70">
          <h3 class="font-semibold text-gray-700">Laporan Produksi</h3>
          <p class="text-sm text-gray-600 mt-1">Fitur akan datang</p>
        </div>
      </div>
    </div>
  </PPICLayout>
</template>
