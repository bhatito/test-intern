import { createRouter, createWebHistory } from 'vue-router'
import ChooseLogin from '@/views/ChooseLogin.vue'
import LoginPPIC from '@/views/LoginPPIC.vue'
import LoginProduksi from '@/views/LoginProduksi.vue'
import DashboardPPIC from '@/views/ppic/Dashboard.vue'
import DashboardProduksi from '@/views/produksi/Dashboard.vue'
import MasterProduk from '@/views/ppic/MasterProduk.vue'
import RencanaProduksi from '@/views/ppic/RencanaProduksi.vue'
import ApprovalRencana from '@/views/produksi/ApprovalRencana.vue'
import OrderProduksi from '@/views/produksi/OrderProduksi.vue'
import LaporanProduksi from '@/views/produksi/LaporanProduksi.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { 
      path: '/', 
      component: ChooseLogin,
      meta: { requiresAuth: false }
    },
    { 
      path: '/login/ppic', 
      component: LoginPPIC,
      meta: { requiresAuth: false }
    },
    { 
      path: '/login/produksi', 
      component: LoginProduksi,
      meta: { requiresAuth: false }
    },
    
    // 🔹 PPIC Routes
    { 
      path: '/ppic', 
      component: DashboardPPIC,
      meta: { requiresAuth: true, department: 'ppic' }
    },
    { 
      path: '/ppic/master-produk', 
      component: MasterProduk,
      meta: { requiresAuth: true, department: 'ppic' }
    },
    { 
      path: '/ppic/rencana-produksi', 
      component: RencanaProduksi,
      meta: { requiresAuth: true, department: 'ppic' }
    },
    
    // 🔹 Produksi Routes
    { 
      path: '/produksi', 
      component: DashboardProduksi,
      meta: { requiresAuth: true, department: 'produksi' }
    },
    { 
      path: '/produksi/persetujuan', 
      component: ApprovalRencana,
      meta: { requiresAuth: true, department: 'produksi' }
    },
    { 
      path: '/produksi/order-produksi', 
      component: OrderProduksi,
      meta: { requiresAuth: true, department: 'produksi' }
    },
    { 
      path: '/produksi/laporan-produksi', 
      component: LaporanProduksi,
      meta: { requiresAuth: true, department: 'produksi' }
    },

    // 🔹 404 Handler
    {
      path: '/:pathMatch(.*)*',
      redirect: '/'
    }
  ],
})

// Navigation Guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')

  // Jika route membutuhkan authentication tapi user belum login
  if (to.meta.requiresAuth && !token) {
    next('/')
    return
  }

  // Jika user sudah login tapi mencoba akses login page
  if ((to.path === '/login/ppic' || to.path === '/login/produksi' || to.path === '/') && token) {
    // Redirect ke dashboard sesuai department
    if (user.department === 'ppic') {
      next('/ppic')
    } else if (user.department === 'produksi') {
      next('/produksi')
    } else {
      next('/')
    }
    return
  }

  // Check department authorization untuk protected routes
  if (to.meta.requiresAuth && to.meta.department) {
    if (user.department !== to.meta.department) {
      // Redirect ke dashboard sesuai department user
      if (user.department === 'ppic') {
        next('/ppic')
      } else if (user.department === 'produksi') {
        next('/produksi')
      } else {
        next('/')
      }
      return
    }
  }

  next()
})

export default router