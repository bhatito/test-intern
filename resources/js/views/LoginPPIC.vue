<template>
  <div class="min-h-dvh flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl shadow p-6">
      <h1 class="text-xl font-semibold mb-4">Login PPIC</h1>
      <LoginForm department="ppic" />
    </div>
  </div>
</template>
<script setup>
import LoginForm from '@/components/LoginForm.vue'
</script>
