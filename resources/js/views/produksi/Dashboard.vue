<script setup>
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import ProduksiLayout from '@/layouts/ProduksiLayout.vue'
import { useAuth } from '@/stores/auth'
import axios from 'axios'

const auth = useAuth()

const statistics = ref({
  total: 0,
  menunggu: 0,
  dalam_proses: 0,
  selesai: 0,
  selesai_bulan_ini: 0
})

const loading = ref(true)
const recentOrders = ref([])
const pendingApprovals = ref(0)
const errorMessage = ref('')

const loadDashboardData = async () => {
  try {
    loading.value = true
    errorMessage.value = ''

    console.log('=== MEMULAI LOAD DATA DASHBOARD ===')
    console.log('User role:', auth.user?.role)

    const statsRes = await axios.get('/api/produksi/dashboard/stats')
    console.log('📊 Stats response:', statsRes.data)
    
    if (statsRes.data.success) {
      statistics.value = statsRes.data.data
      console.log('✅ Statistik berhasil dimuat:', statistics.value)
    } else {
      console.error('❌ Gagal memuat statistik:', statsRes.data)
    }

    try {
      const ordersRes = await axios.get('/api/produksi/production-orders?limit=5')
      console.log('📦 Orders response:', ordersRes.data)
      
      if (ordersRes.data.success) {
        recentOrders.value = ordersRes.data.data || []
        console.log('✅ Order terbaru berhasil dimuat:', recentOrders.value)
      } else {
        console.error('❌ Gagal memuat orders:', ordersRes.data)
        recentOrders.value = []
      }
    } catch (orderError) {
      console.error('❌ Error loading orders:', orderError)
      recentOrders.value = []
    }

    if (auth.user?.role === 'managerpproduksi') {
      try {
        const approvalsRes = await axios.get('/api/produksi/dashboard/pending-approvals-count')
        console.log('✅ Approvals response:', approvalsRes.data)
        
        if (approvalsRes.data.success) {
          pendingApprovals.value = approvalsRes.data.count || 0
          console.log('📋 Pending approvals:', pendingApprovals.value)
        }
      } catch (approvalError) {
        console.error('❌ Error loading approvals:', approvalError)
        pendingApprovals.value = 0
      }
    }

  } catch (error) {
    console.error('❌ Gagal memuat data dashboard:', error)
    console.error('Error details:', error.response?.data || error.message)
    errorMessage.value = 'Gagal memuat data dashboard. Silakan refresh halaman.'

    statistics.value = {
      total: 12,
      menunggu: 3,
      dalam_proses: 6,
      selesai: 3,
      selesai_bulan_ini: 2
    }
    recentOrders.value = []
  } finally {
    loading.value = false
    console.log('=== LOAD DATA SELESAI ===')
    console.log('Statistics:', statistics.value)
    console.log('Recent orders:', recentOrders.value)
    console.log('Pending approvals:', pendingApprovals.value)
  }
}

const formatNumber = (num) => {
  return new Intl.NumberFormat('id-ID').format(num)
}
const getStatusBadge = (status) => {
  const statusConfig = {
    'menunggu': { class: 'bg-yellow-100 text-yellow-800', label: 'Menunggu', icon: '⏳' },
    'dalam_proses': { class: 'bg-blue-100 text-blue-800', label: 'Dalam Proses', icon: '⚙️' },
    'selesai': { class: 'bg-green-100 text-green-800', label: 'Selesai', icon: '✅' },
    'ditutup': { class: 'bg-gray-100 text-gray-800', label: 'Ditutup', icon: '🏁' }
  }
  
  return statusConfig[status] || { class: 'bg-gray-100 text-gray-800', label: status, icon: '📋' }
}

const quickActions = computed(() => {
  const baseActions = [
    {
      title: 'Order Produksi',
      description: 'Lihat dan kerjakan order produksi',
      icon: '📦',
      path: '/produksi/order-produksi',
      color: 'blue'
    }
  ]

  if (auth.user?.role === 'managerpproduksi') {
    baseActions.unshift(
      {
        title: 'Persetujuan Rencana',
        description: `Review ${pendingApprovals.value} rencana menunggu`,
        icon: '✅',
        path: '/produksi/persetujuan',
        color: 'orange',
        badge: pendingApprovals.value > 0 ? pendingApprovals.value : null
      },
      {
        title: 'Laporan Produksi',
        description: 'Lihat laporan dan statistik produksi',
        icon: '📊',
        path: '/produksi/laporan-produksi',
        color: 'green'
      }
    )
  }

  return baseActions
})

const productionEfficiency = computed(() => {
  const total = statistics.value.total || 1
  const completed = statistics.value.selesai_bulan_ini || 0
  return Math.round((completed / total) * 100)
})

const refreshData = () => {
  loadDashboardData()
}

onMounted(() => {
  loadDashboardData()
})
</script>

<template>
  <ProduksiLayout>
    <div class="space-y-6">
      <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-xl p-4">
        <div class="flex items-center gap-3">
          <span class="text-red-600 text-lg">⚠️</span>
          <div>
            <p class="text-red-800 font-medium">Terjadi Kesalahan</p>
            <p class="text-red-600 text-sm">{{ errorMessage }}</p>
          </div>
          <button 
            @click="loadDashboardData"
            class="ml-auto px-3 py-1 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors"
          >
            Coba Lagi
          </button>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow p-6">
        <div class="flex justify-between items-start mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Produksi</h1>
            <p class="text-gray-600 mt-1">
              Selamat datang, <span class="font-semibold text-green-600">{{ auth.user?.name }}</span>! 
              Anda login sebagai <span class="font-semibold capitalize">{{ auth.user?.role }}</span>.
            </p>
          </div>
          <button
            @click="refreshData"
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors"
          >
            <svg 
              :class="['w-4 h-4', loading ? 'animate-spin' : '']" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" 
              />
            </svg>
            {{ loading ? 'Memuat...' : 'Refresh' }}
          </button>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-blue-600 text-sm font-medium">Total Order</p>
                <p class="text-2xl font-bold text-blue-800 mt-1">{{ formatNumber(statistics.total) }}</p>
                <p class="text-xs text-blue-600 mt-1">Semua order produksi</p>
              </div>
              <div class="text-blue-600 text-2xl">📦</div>
            </div>
          </div>

          <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-orange-600 text-sm font-medium">Menunggu</p>
                <p class="text-2xl font-bold text-orange-800 mt-1">{{ formatNumber(statistics.menunggu) }}</p>
                <p class="text-xs text-orange-600 mt-1">Order menunggu produksi</p>
              </div>
              <div class="text-orange-600 text-2xl">⏳</div>
            </div>
          </div>

          <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-yellow-600 text-sm font-medium">Dalam Proses</p>
                <p class="text-2xl font-bold text-yellow-800 mt-1">{{ formatNumber(statistics.dalam_proses) }}</p>
                <p class="text-xs text-yellow-600 mt-1">Sedang diproduksi</p>
              </div>
              <div class="text-yellow-600 text-2xl">⚙️</div>
            </div>
          </div>

          <div class="bg-green-50 border border-green-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-green-600 text-sm font-medium">Selesai Bulan Ini</p>
                <p class="text-2xl font-bold text-green-800 mt-1">{{ formatNumber(statistics.selesai_bulan_ini) }}</p>
                <p class="text-xs text-green-600 mt-1">
                  Efisiensi: {{ productionEfficiency }}%
                </p>
              </div>
              <div class="text-green-600 text-2xl">✅</div>
            </div>
          </div>
        </div>

        <div class="mb-8">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Aksi Cepat</h3>
          <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            <RouterLink
              v-for="action in quickActions"
              :key="action.path"
              :to="action.path"
              class="bg-white border-2 border-gray-200 rounded-xl p-4 hover:border-green-500 hover:shadow-md transition-all duration-200 group"
            >
              <div class="flex items-center gap-3">
                <div 
                  class="text-2xl p-2 rounded-lg group-hover:scale-110 transition-transform"
                  :class="{
                    'bg-blue-100 text-blue-600': action.color === 'blue',
                    'bg-green-100 text-green-600': action.color === 'green',
                    'bg-orange-100 text-orange-600': action.color === 'orange'
                  }"
                >
                  {{ action.icon }}
                </div>
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <h4 class="font-semibold text-gray-800 group-hover:text-green-600 transition-colors">
                      {{ action.title }}
                    </h4>
                    <span 
                      v-if="action.badge"
                      class="bg-red-500 text-white text-xs rounded-full min-w-[20px] h-5 flex items-center justify-center animate-pulse"
                    >
                      {{ action.badge }}
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">{{ action.description }}</p>
                </div>
              </div>
            </RouterLink>
          </div>
        </div>
      </div>

      <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Order Produksi Terbaru</h3>
            <RouterLink 
              to="/produksi/order-produksi"
              class="text-sm text-green-600 hover:text-green-700 font-medium"
            >
              Lihat Semua →
            </RouterLink>
          </div>

          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
            <p class="text-gray-500 mt-2">Memuat data order...</p>
          </div>

          <div v-else-if="recentOrders.length === 0" class="text-center py-8">
            <div class="text-gray-400 text-4xl mb-3">📦</div>
            <h4 class="text-lg font-medium text-gray-900 mb-2">Belum ada order produksi</h4>
            <p class="text-gray-500">Order akan muncul setelah rencana produksi disetujui</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="order in recentOrders"
              :key="order.id"
              class="border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200"
            >
              <div class="p-4">
                <div class="flex items-start justify-between mb-3">
                  <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                      <span class="font-semibold text-gray-900">{{ order.nomor_order }}</span>
                      <span 
                        :class="['px-3 py-1 text-sm font-medium rounded-full capitalize flex items-center gap-1', getStatusBadge(order.status).class]"
                      >
                        {{ getStatusBadge(order.status).icon }}
                        {{ getStatusBadge(order.status).label }}
                      </span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                      <div>
                        <span class="text-gray-500">Target:</span>
                        <p class="font-medium text-gray-900 mt-1">
                          {{ formatNumber(order.target_jumlah) }} pcs
                        </p>
                      </div>
                      
                      <div>
                        <span class="text-gray-500">Aktual:</span>
                        <p class="font-medium text-gray-900 mt-1">
                          {{ formatNumber(order.jumlah_aktual || 0) }} pcs
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex gap-2 mt-3">
                  <RouterLink 
                    v-if="order.status === 'menunggu'"
                    :to="`/produksi/order-produksi/${order.id}`"
                    class="flex-1 text-center px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors"
                  >
                    Mulai Produksi
                  </RouterLink>
                  <RouterLink 
                    v-else
                    :to="`/produksi/order-produksi/${order.id}`"
                    class="flex-1 text-center px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
                  >
                    Lihat Detail
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Statistik Produksi</h3>
          
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
            <p class="text-gray-500 mt-2">Memuat statistik...</p>
          </div>

          <div v-else class="space-y-4">
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="font-medium text-gray-800 mb-3">Ringkasan Progress</h4>
              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Order Menunggu</span>
                  <span class="font-semibold text-orange-600">{{ statistics.menunggu }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Dalam Proses</span>
                  <span class="font-semibold text-blue-600">{{ statistics.dalam_proses }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Selesai</span>
                  <span class="font-semibold text-green-600">{{ statistics.selesai }}</span>
                </div>
              </div>
            </div>

            <div class="bg-green-50 rounded-lg p-4">
              <h4 class="font-medium text-green-800 mb-2">Efisiensi Produksi</h4>
              <div class="flex items-center gap-3">
                <div class="flex-1 bg-green-200 rounded-full h-3">
                  <div 
                    class="bg-green-600 h-3 rounded-full transition-all duration-500"
                    :style="{ width: `${productionEfficiency}%` }"
                  ></div>
                </div>
                <span class="text-lg font-bold text-green-700">{{ productionEfficiency }}%</span>
              </div>
              <p class="text-xs text-green-600 mt-2">
                {{ statistics.selesai_bulan_ini }} order selesai dari {{ statistics.total }} total
              </p>
            </div>

            <div class="bg-blue-50 rounded-lg p-4">
              <h4 class="font-medium text-blue-800 mb-2">Info Cepat</h4>
              <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2">
                  <span class="text-blue-600">📦</span>
                  <span class="text-gray-700">Total order aktif: {{ statistics.total }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-green-600">✅</span>
                  <span class="text-gray-700">Selesai bulan ini: {{ statistics.selesai_bulan_ini }}</span>
                </div>
                <div v-if="auth.user?.role === 'managerpproduksi'" class="flex items-center gap-2">
                  <span class="text-orange-600">⏳</span>
                  <span class="text-gray-700">Menunggu persetujuan: {{ pendingApprovals }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ProduksiLayout>
</template>