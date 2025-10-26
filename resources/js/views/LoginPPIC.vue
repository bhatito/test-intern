<template>
  <div class="min-h-dvh flex items-center justify-center bg-gradient-to-br from-amber-50 to-orange-100 p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl p-8">
      <div class="text-center mb-8">
        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Login PPIC</h1>
        <p class="text-gray-600">Masuk ke sistem produksi</p>
      </div>
      <LoginForm department="ppic" />
    </div>
  </div>
</template>

<script setup>
import LoginForm from '@/components/LoginForm.vue'
</script>