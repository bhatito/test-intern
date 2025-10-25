<script setup>
import { ref } from 'vue'
import { useAuth } from '@/stores/auth'

const props = defineProps({ department: { type: String, required: true } })
const email = ref(''); const password = ref(''); const errorMsg = ref(null); const loading = ref(false)
const auth = useAuth()

const submit = async () => {
  errorMsg.value = null; loading.value = true
  try {
    const redirect = await auth.login({ email: email.value, password: password.value, department: props.department })
    window.location.assign(redirect)
  } catch (e) {
    errorMsg.value = e?.response?.data?.message || 'Login gagal'
  } finally { loading.value = false }
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-4">
    <input v-model="email" type="email" required placeholder="Email" class="w-full border rounded p-2" />
    <input v-model="password" type="password" required placeholder="Password" class="w-full border rounded p-2" />
    <button :disabled="loading" class="w-full rounded bg-black text-white p-2">
      {{ loading ? 'Masuk...' : 'Masuk' }}
    </button>
    <p v-if="errorMsg" class="text-red-600 text-sm">{{ errorMsg }}</p>
  </form>
</template>
