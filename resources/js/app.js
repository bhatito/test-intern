import './bootstrap'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import axios from 'axios';
axios.defaults.withCredentials = true;

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
axios.get('/sanctum/csrf-cookie').then(() => {
    // Axios will include the CSRF token
});