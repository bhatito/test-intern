import { createRouter, createWebHistory } from 'vue-router'
import ChooseLogin from '@/views/ChooseLogin.vue'
import LoginPPIC from '@/views/LoginPPIC.vue'
import LoginProduksi from '@/views/LoginProduksi.vue'
import DashboardPPIC from '@/views/ppic/Dashboard.vue'
import DashboardProduksi from '@/views/produksi/Dashboard.vue'
import MasterProduk from '@/views/ppic/MasterProduk.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: ChooseLogin },
    { path: '/login/ppic', component: LoginPPIC },
    { path: '/login/produksi', component: LoginProduksi },
    { path: '/ppic', component: DashboardPPIC },
    { path: '/ppic/master-produk', component: MasterProduk },
    { path: '/produksi', component: DashboardProduksi },
  ],
})

// (opsional) guard: hanya user PPIC boleh ke halaman PPIC
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')

  if (to.path.startsWith('/ppic') && user.department !== 'ppic') {
    return next('/')
  }
  if (to.path.startsWith('/produksi') && user.department !== 'produksi') {
    return next('/')
  }

  if (to.meta.requiresAuth && !token) {
    return next('/')
  }

  next()
})

export default router
