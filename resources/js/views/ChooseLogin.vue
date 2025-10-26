<template>
  <div class="min-h-dvh flex items-center justify-center bg-gradient-to-br from-slate-50 to-blue-100 px-4">
    <div class="text-center p-8 bg-white/90 backdrop-blur-lg shadow-2xl rounded-3xl max-w-md w-full border border-white/20">
      
      <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-3">
        Selamat Datang
      </h1>
      <p class="text-gray-600 mb-8 text-lg">Pilih departemen untuk melanjutkan</p>

      <div class="space-y-4">
        <RouterLink
          to="/login/ppic"
          class="group block w-full rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white py-4 px-6 text-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center gap-3"
        >
          <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Masuk sebagai PPIC
        </RouterLink>

        <RouterLink
          to="/login/produksi"
          class="group block w-full rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white py-4 px-6 text-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center gap-3"
        >
          <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
          </svg>
          Masuk sebagai Produksi
        </RouterLink>
      </div>

      <p class="mt-12 text-sm text-gray-500">
        &copy; {{ new Date().getFullYear() }} Sistem Produksi — Bhatito Sharoni Putra
      </p>
    </div>
  </div>
</template>

<script setup>
</script>