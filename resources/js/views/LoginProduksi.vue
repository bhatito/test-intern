<template>
  <div class="min-h-dvh flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl p-8">
      <div class="text-center mb-8">
        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Login Produksi</h1>
        <p class="text-gray-600">Masuk ke sistem produksi</p>
      </div>
      <LoginForm department="produksi" />
    </div>
  </div>
</template>

<script setup>
import LoginForm from '@/components/LoginForm.vue'
</script>