<template>
  <div class="min-h-dvh flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-200">
    <div class="text-center p-8 bg-white/80 backdrop-blur-md shadow-xl rounded-3xl max-w-md w-full">
      <h1 class="text-3xl font-bold text-gray-800 mb-2">Selamat Datang 👋</h1>
      <p class="text-gray-600 mb-8">Silakan pilih login sesuai departemen Anda</p>

      <div class="grid gap-4">
        <RouterLink
          to="/login/ppic"
          class="block w-full rounded-xl bg-amber-500 hover:bg-amber-600 text-white py-3 text-lg font-semibold transition"
        >
          Masuk sebagai PPIC
        </RouterLink>

        <RouterLink
          to="/login/produksi"
          class="block w-full rounded-xl bg-blue-600 hover:bg-blue-700 text-white py-3 text-lg font-semibold transition"
        >
          Masuk sebagai Produksi
        </RouterLink>
      </div>

      <p class="mt-10 text-xs text-gray-400">
        &copy; {{ new Date().getFullYear() }} Sistem Produksi — Lea Workspace
      </p>
    </div>
  </div>
</template>

<script setup>
// Tidak perlu script kompleks di sini — cuma halaman navigasi.
</script>
