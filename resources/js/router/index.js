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
import HistoryRencana from '../views/ppic/HistoryRencana.vue'
import LaporanPPIC from '../views/ppic/LaporanPPIC.vue'
import HistoryProduksi from '../views/produksi/HistoryProduksi.vue' // Import yang benar

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
    { 
      path: '/ppic/history-rencana', 
      component: HistoryRencana,
      meta: { 
        requiresAuth: true, 
        department: 'ppic',
        role: 'managerppic' 
      }
    },
    { 
      path: '/ppic/laporan-rencana', 
      component: LaporanPPIC,
      meta: { 
        requiresAuth: true, 
        department: 'ppic',
        role: 'managerppic' 
      }
    },
    { 
      path: '/produksi', 
      component: DashboardProduksi,
      meta: { requiresAuth: true, department: 'produksi' }
    },
    { 
      path: '/produksi/persetujuan', 
      component: ApprovalRencana,
      meta: { 
        requiresAuth: true, 
        department: 'produksi',
        role: 'managerpproduksi' 
      }
    },
    { 
      path: '/produksi/order-produksi', 
      component: OrderProduksi,
      meta: { requiresAuth: true, department: 'produksi' }
    },
    { 
      path: '/produksi/laporan-produksi', 
      component: LaporanProduksi,
      meta: { 
        requiresAuth: true, 
        department: 'produksi',
        role: 'managerpproduksi' 
      }
    },
    {
      path: '/produksi/history-order',
      component: HistoryProduksi, 
      meta: { 
        requiresAuth: true,
        department: 'produksi',
        role: 'managerpproduksi'
      }
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/'
    }
  ],
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')

  if (to.meta.requiresAuth && !token) {
    next('/')
    return
  }

  if ((to.path === '/login/ppic' || to.path === '/login/produksi' || to.path === '/') && token) {
    if (user.department === 'ppic') {
      next('/ppic')
    } else if (user.department === 'produksi') {
      next('/produksi')
    } else {
      next('/')
    }
    return
  }

  if (to.meta.requiresAuth && to.meta.department) {
    if (user.department !== to.meta.department) {
      if (user.department === 'ppic') {
        next('/ppic')
      } else if (user.department === 'produksi') {
        next('/produksi')
      } else {
        next('/')
      }
      return
    }

    if (to.meta.role && user.role !== to.meta.role) {
      if (user.department === 'ppic' && user.role === 'staffppic') {
        next('/ppic/rencana-produksi')
        return
      }
      
      if (user.department === 'produksi' && user.role === 'staffproduksi') {
        next('/produksi/order-produksi')
        return
      }
      
      next(`/${user.department}`)
      return
    }
  }

  next()
})

export default router