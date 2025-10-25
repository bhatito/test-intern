<script setup>
import ProduksiLayout from '@/layouts/ProduksiLayout.vue'
</script>

<template>
  <ProduksiLayout>
    <div class="bg-white rounded-2xl shadow p-6">
      <h2 class="text-lg font-semibold mb-2">
        Selamat datang di Dashboard Produksi 👋
      </h2>
      <p class="text-gray-600 mb-6">
        Anda berhasil login sebagai <strong>Staff / Manager Produksi</strong>.
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          class="bg-gray-50 border border-gray-100 rounded-xl p-4 shadow-sm hover:shadow transition"
        >
          <p class="text-gray-500 text-sm">Total Order</p>
          <p class="text-3xl font-bold text-gray-800 mt-1">0</p>
        </div>

        <div
          class="bg-gray-50 border border-gray-100 rounded-xl p-4 shadow-sm hover:shadow transition"
        >
          <p class="text-gray-500 text-sm">Sedang Dikerjakan</p>
          <p class="text-3xl font-bold text-gray-800 mt-1">0</p>
        </div>

        <div
          class="bg-gray-50 border border-gray-100 rounded-xl p-4 shadow-sm hover:shadow transition"
        >
          <p class="text-gray-500 text-sm">Selesai Bulan Ini</p>
          <p class="text-3xl font-bold text-gray-800 mt-1">0</p>
        </div>
      </div>
    </div>
  </ProduksiLayout>
</template>
