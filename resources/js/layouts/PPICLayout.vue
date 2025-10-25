<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/stores/auth'

const auth = useAuth()
const route = useRoute()
const router = useRouter()

const mobileOpen = ref(false)
const pendingApprovals = ref(0)
const loadingApprovals = ref(false)

// Deteksi role
const isManagerPPIC = computed(
  () => auth.user?.department === 'ppic' && auth.user?.role === 'managerppic'
)

const isStaffPPIC = computed(
  () => auth.user?.department === 'ppic' && auth.user?.role === 'staffppic'
)

const isActive = (path) => route.path === path

const goToDashboard = () => router.push('/ppic')

// 🔔 Ambil jumlah rencana yang menunggu persetujuan (hanya untuk manager)
const loadPendingApprovals = async () => {
  if (!isManagerPPIC.value) return
  
  try {
    loadingApprovals.value = true
    console.log('🔄 Memuat notifikasi persetujuan untuk Manager PPIC...')
    
    // ✅ GUNAKAN ENDPOINT PPIC, BUKAN MANAGER
    const res = await axios.get('/api/ppic/production-plans/statistics')
    
    console.log('📊 Response statistics PPIC:', res.data)
    
    if (res.data.success) {
      pendingApprovals.value = res.data.data?.menunggu_persetujuan || 0
      console.log(`✅ Notifikasi: ${pendingApprovals.value} rencana menunggu persetujuan`)
    } else {
      pendingApprovals.value = 0
      console.warn('❌ Response tidak success:', res.data.message)
    }
  } catch (error) {
    console.error('❌ Gagal memuat notifikasi:', error)
    
    // Fallback: hitung manual dari daftar rencana
    if (error.response?.status === 404 || error.response?.status === 405) {
      console.log('🔄 Mencoba fallback: hitung manual dari daftar rencana...')
      try {
        const res = await axios.get('/api/ppic/production-plans')
        const plans = res.data.data || res.data || []
        pendingApprovals.value = plans.filter(plan => plan.status === 'menunggu_persetujuan').length
        console.log(`✅ Fallback berhasil: ${pendingApprovals.value} rencana menunggu`)
      } catch (fallbackError) {
        console.error('❌ Fallback juga gagal:', fallbackError)
        pendingApprovals.value = 0
      }
    } else {
      pendingApprovals.value = 0
    }
  } finally {
    loadingApprovals.value = false
  }
}

const logout = async () => {
  try {
    await axios.post('/api/logout')
  } catch (e) {
    console.error(e)
  } finally {
    auth.logout()
    router.push('/')
  }
}

// Menu berdasarkan role
const menuItems = computed(() => {
  const baseMenu = [
    { 
      path: '/ppic', 
      label: 'Dashboard', 
      icon: '📊',
      show: true
    }
  ]

  // Menu untuk Staff PPIC
  if (isStaffPPIC.value) {
    return [
      ...baseMenu,
      { 
        path: '/ppic/rencana-produksi', 
        label: 'Rencana Produksi', 
        icon: '📝',
        show: true
      }
    ]
  }

  // Menu untuk Manager PPIC
  if (isManagerPPIC.value) {
    return [
      ...baseMenu,
      { 
        path: '/ppic/master-produk', 
        label: 'Master Produk', 
        icon: '📦',
        show: true
      },
      { 
        path: '/ppic/rencana-produksi', 
        label: 'Rencana Produksi', 
        icon: '📝',
        show: true,
        badge: pendingApprovals.value > 0 ? pendingApprovals.value : null
      },
      { 
        path: '/ppic/history-rencana', 
        label: 'History Rencana', 
        icon: '📋',
        show: true
      },
      { 
        path: '/ppic/laporan-produksi', 
        label: 'Laporan', 
        icon: '📈',
        show: true
      }
    ]
  }

  return baseMenu
})

// Refresh notifikasi (hanya untuk manager)
onMounted(() => {
  console.log('🚀 PPICLayout mounted')
  console.log('👤 User:', auth.user)
  console.log('🎯 Is Manager PPIC:', isManagerPPIC.value)
  
  if (isManagerPPIC.value) {
    console.log('🔔 Memulai notifikasi untuk Manager PPIC...')
    loadPendingApprovals()
    // Refresh setiap 60 detik
    setInterval(loadPendingApprovals, 60000)
  } else {
    console.log('ℹ️ User bukan Manager PPIC, notifikasi dimatikan')
  }
})
</script>

<template>
  <div class="min-h-dvh bg-gray-50 flex flex-col">
    <!-- HEADER -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-30">
      <div class="max-w-7xl mx-auto flex justify-between items-center px-4 sm:px-6 py-4">
        <!-- Brand -->
        <div class="flex items-center gap-3">
          <h1
            class="text-lg sm:text-xl font-bold text-gray-800 cursor-pointer flex items-center gap-2"
            @click="goToDashboard"
          >
            <span class="text-blue-600">🏭</span>
            PPIC System
            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full capitalize">
              {{ auth.user?.role }}
            </span>
          </h1>

          <!-- Desktop Nav -->
          <nav class="hidden md:flex gap-2 ml-6 text-sm font-medium">
            <RouterLink
              v-for="item in menuItems"
              :key="item.path"
              :to="item.path"
              class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-2 relative"
              :class="isActive(item.path) 
                ? 'bg-blue-100 text-blue-700 border border-blue-200' 
                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800'"
            >
              <span>{{ item.icon }}</span>
              {{ item.label }}
              <span 
                v-if="item.badge && item.badge > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center animate-pulse"
              >
                {{ item.badge }}
              </span>
            </RouterLink>
          </nav>
        </div>

        <!-- User info + actions -->
        <div class="flex items-center gap-4">
          <!-- Notifikasi Badge (hanya manager) -->
          <div 
            v-if="isManagerPPIC && pendingApprovals > 0 && !loadingApprovals"
            class="hidden sm:flex items-center gap-2 bg-orange-50 border border-orange-200 rounded-lg px-3 py-1.5"
          >
            <span class="text-orange-600 text-sm font-medium">
              ⏳ {{ pendingApprovals }} menunggu persetujuan
            </span>
          </div>

          <!-- Loading State untuk Manager -->
          <div 
            v-if="isManagerPPIC && loadingApprovals"
            class="hidden sm:flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5"
          >
            <span class="text-gray-600 text-sm">
              🔄 Memuat...
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

          <!-- Mobile menu button -->
          <button
            class="md:hidden p-2 rounded-lg border hover:bg-gray-50 transition-colors"
            @click="mobileOpen = !mobileOpen"
            aria-label="Toggle menu"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <!-- Logout button -->
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

      <!-- Mobile Dropdown Menu -->
      <div v-if="mobileOpen" class="md:hidden bg-white border-t shadow-lg">
        <nav class="flex flex-col px-4 py-3 gap-1 text-sm font-medium">
          <RouterLink
            v-for="item in menuItems"
            :key="item.path"
            :to="item.path"
            @click="mobileOpen = false"
            class="px-4 py-3 rounded-lg flex items-center gap-3 transition-colors relative"
            :class="isActive(item.path) 
              ? 'bg-blue-100 text-blue-700 border border-blue-200' 
              : 'hover:bg-gray-100 text-gray-700'"
          >
            <span class="text-lg">{{ item.icon }}</span>
            {{ item.label }}
            <span 
              v-if="item.badge && item.badge > 0"
              class="absolute right-4 bg-red-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center animate-pulse"
            >
              {{ item.badge }}
            </span>
          </RouterLink>

          <!-- Mobile Notifikasi (hanya manager) -->
          <div 
            v-if="isManagerPPIC && pendingApprovals > 0 && !loadingApprovals"
            class="mt-2 px-4 py-3 bg-orange-50 border border-orange-200 rounded-lg"
          >
            <p class="text-orange-700 text-sm font-medium flex items-center gap-2">
              <span>⏳</span>
              {{ pendingApprovals }} rencana menunggu persetujuan
            </p>
          </div>

          <!-- Mobile Loading -->
          <div 
            v-if="isManagerPPIC && loadingApprovals"
            class="mt-2 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg"
          >
            <p class="text-gray-600 text-sm flex items-center gap-2">
              <span>🔄</span>
              Memuat notifikasi...
            </p>
          </div>

          <!-- Mobile Logout -->
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
    </header>

    <!-- MAIN CONTENT -->
    <div class="flex flex-1">
      <!-- SIDEBAR (desktop) - hanya untuk manager -->
      

      <!-- CONTENT AREA -->
      <main 
        class="flex-1 p-4 sm:p-6 min-w-0"
        :class="{ 'max-w-7xl mx-auto w-full': !isManagerPPIC }"
      >
        <slot />
      </main>
    </div>

    <!-- FOOTER -->
    <footer class="text-center text-gray-500 text-xs sm:text-sm py-4 border-t bg-white">
      &copy; {{ new Date().getFullYear() }} PPIC System — 
      <span class="capitalize">{{ auth.user?.role }}</span> Access
    </footer>
  </div>
</template>