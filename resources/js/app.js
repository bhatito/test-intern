import './bootstrap'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import axios from 'axios'

createApp(App).use(createPinia()).use(router).mount('#app')

const token = localStorage.getItem('token')
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  axios.get('/api/me')
    .then(res => console.log('Session OK', res.data))
    .catch(() => {
      // kalau gagal (karena token tidak ada di DB), logout
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      delete axios.defaults.headers.common['Authorization']
      window.location.href = '/' // redirect ke halaman pilih login
    })
}
