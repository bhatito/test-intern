import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@/stores/auth'
import ChooseLogin from '@/views/ChooseLogin.vue'
import LoginPPIC from '@/views/LoginPPIC.vue'
import LoginProduksi from '@/views/LoginProduksi.vue'

const DashboardPPIC = () => import('@/views/ppic/Dashboard.vue')
const DashboardProduksi = () => import('@/views/produksi/Dashboard.vue')

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: ChooseLogin, meta: { guestOnly: true } },
    { path: '/login/ppic', component: LoginPPIC, meta: { guestOnly: true } },
    { path: '/login/produksi', component: LoginProduksi, meta: { guestOnly: true } },

    { path: '/ppic', component: DashboardPPIC, meta: { requiresAuth: true, department: 'ppic' } },
    { path: '/produksi', component: DashboardProduksi, meta: { requiresAuth: true, department: 'produksi' } },

    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach((to) => {
  const auth = useAuth()
  if (to.meta.guestOnly && auth.isAuthenticated) {
    return auth.department === 'ppic' ? '/ppic' : '/produksi'
  }
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return to.meta.department === 'ppic' ? '/login/ppic' : '/login/produksi'
  }
  if (to.meta.requiresAuth && auth.isAuthenticated) {
    const dept = to.meta.department
    if (dept && auth.department !== dept) {
      return auth.department === 'ppic' ? '/ppic' : '/produksi'
    }
  }
})

export default router
