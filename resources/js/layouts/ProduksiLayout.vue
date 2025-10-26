<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/stores/auth'

const auth = useAuth()
const route = useRoute()
const mobileOpen = ref(false)

const pendingApprovals = ref(0)
const approvedOrders = ref(0)
const notificationInterval = ref(null)

const isManagerProduksi = computed(() =>
  auth.user?.department === 'produksi' && auth.user?.role === 'managerpproduksi'
)

const isActive = (path) => route.path === path

const fetchNotifications = async () => {
  try {
    if (isManagerProduksi.value) {
      const approvalsResponse = await axios.get('/api/produksi/dashboard/pending-count')
      pendingApprovals.value = approvalsResponse.data.count || 0
    }
    const ordersResponse = await axios.get('/api/produksi/dashboard/approved-orders-count')
    approvedOrders.value = ordersResponse.data.count || 0
  } catch (error) {
    console.error('Error fetching notifications:', error)
  }
}

const totalNotifications = computed(() => {
  let total = 0
  if (isManagerProduksi.value) {
    total += pendingApprovals.value
  }
  total += approvedOrders.value
  return total
})

const formatBadgeCount = (count) => {
  return count > 99 ? '99+' : count
}

const startNotificationPolling = () => {
  fetchNotifications()
  notificationInterval.value = setInterval(fetchNotifications, 30000)
}

const logout = async () => {
  try {
    await axios.post('/api/logout')
  } catch (e) {
    console.error(e)
  } finally {
    if (notificationInterval.value) {
      clearInterval(notificationInterval.value)
    }
    auth.logout()
    window.location.assign('/')
  }
}

const menuItems = computed(() => {
  const items = [
    { 
      path: '/produksi', 
      label: 'Dashboard', 
      icon: '🏠',
      badge: 0
    },
    { 
      path: '/produksi/order-produksi', 
      label: 'Order Produksi', 
      icon: '📦',
      badge: approvedOrders.value
    },
  ]

  if (isManagerProduksi.value) {
    items.push(
      { 
        path: '/produksi/laporan-produksi', 
        label: 'Laporan Produksi', 
        icon: '📋',
        badge: 0
      },
      { 
        path: '/produksi/history-order', 
        label: 'History Order', 
        icon: '📊',
        badge: 0
      },
      { 
        path: '/produksi/persetujuan', 
        label: 'Persetujuan', 
        icon: '✅',
        badge: pendingApprovals.value
      }
    )
  }

  return items
})

onMounted(() => {
  startNotificationPolling()
})

onUnmounted(() => {
  if (notificationInterval.value) {
    clearInterval(notificationInterval.value)
  }
})
</script>

<template>
  <div class="min-h-dvh bg-gray-50 flex flex-col">
    <header class="bg-white shadow-sm border-b sticky top-0 z-30">
      <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center px-4 sm:px-6 py-4">
          <div class="flex items-center gap-3">
            <h1 class="text-lg sm:text-xl font-bold text-gray-800 cursor-pointer flex items-center gap-2">
              <span class="text-green-600">🏭</span>
              Sistem Produksi
              <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full capitalize">
                {{ auth.user?.role }}
              </span>
            </h1>
            <nav class="hidden md:flex gap-1 ml-6 text-sm font-medium">
              <RouterLink
                v-for="item in menuItems"
                :key="item.path"
                :to="item.path"
                class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-2 relative"
                :class="isActive(item.path) 
                  ? 'bg-green-100 text-green-700 border border-green-200' 
                  : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800'"
              >
                <span>{{ item.icon }}</span>
                {{ item.label }}
                <span 
                  v-if="item.badge > 0"
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1"
                >
                  {{ formatBadgeCount(item.badge) }}
                </span>
              </RouterLink>
            </nav>
          </div>
          <div class="flex items-center gap-4">
            <div v-if="totalNotifications > 0" class="relative">
              <div class="w-2 h-2 bg-red-500 rounded-full absolute -top-0 -right-0 animate-pulse"></div>
              <span class="text-gray-600">
                🔔
              </span>
            </div>
            <div class="hidden sm:block text-right">
              <p class="text-gray-700 text-sm font-medium">
                {{ auth.user?.name }}
              </p>
              <p class="text-gray-400 text-xs capitalize">
                {{ auth.user?.department }} • {{ auth.user?.role }}
              </p>
            </div>
            <button
              class="md:hidden p-2 rounded-lg border hover:bg-gray-50 transition-colors relative"
              @click="mobileOpen = !mobileOpen"
              aria-label="Toggle menu"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                   viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <span 
                v-if="totalNotifications > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-[16px] h-[16px] flex items-center justify-center"
              >
                {{ formatBadgeCount(totalNotifications) }}
              </span>
            </button>
            <button
              @click="logout"
              class="hidden sm:inline-flex items-center gap-2 px-4 py-2 text-sm bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Logout
            </button>
          </div>
        </div>
        <div v-if="mobileOpen" class="md:hidden bg-white border-t shadow-lg">
          <nav class="flex flex-col px-4 py-3 gap-1 text-sm font-medium">
            <RouterLink
              v-for="item in menuItems"
              :key="item.path"
              :to="item.path"
              @click="mobileOpen = false"
              class="px-4 py-3 rounded-lg flex items-center gap-3 transition-colors relative"
              :class="isActive(item.path) 
                ? 'bg-green-100 text-green-700 border border-green-200' 
                : 'hover:bg-gray-100 text-gray-700'"
            >
              <span class="text-lg">{{ item.icon }}</span>
              {{ item.label }}
              <span 
                v-if="item.badge > 0"
                class="ml-auto bg-red-500 text-white text-xs rounded-full min-w-[20px] h-[20px] flex items-center justify-center px-1"
              >
                {{ formatBadgeCount(item.badge) }}
              </span>
            </RouterLink>
            <button
              @click="logout"
              class="mt-3 px-4 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors flex items-center gap-3 text-left"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Logout
            </button>
          </nav>
        </div>
      </div>
    </header>
    <main class="flex-1 p-4 sm:p-6">
      <div class="max-w-7xl mx-auto w-full">
        <slot />
      </div>
    </main>
    <footer class="text-center text-gray-500 text-xs sm:text-sm py-4 border-t bg-white">
      &copy; {{ new Date().getFullYear() }} Sistem Produksi — 
      <span class="capitalize">{{ auth.user?.role }}</span> Access
    </footer>
  </div>
</template>