<script setup>
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import PPICLayout from '@/layouts/PPICLayout.vue'
import { useAuth } from '@/stores/auth'
import axios from 'axios'

const auth = useAuth()

// Data statistik
const statistics = ref({
  totalProduk: 0,
  totalRencana: 0,
  rencanaDraft: 0,
  rencanaMenunggu: 0,
  rencanaDisetujui: 0,
  rencanaMenjadiOrder: 0
})

const loading = ref(true)
const recentPlans = ref([])

// Load data dashboard
const loadDashboardData = async () => {
  try {
    loading.value = true

    // Load statistik
    const statsRes = await axios.get('/api/ppic/dashboard')
    if (statsRes.data.success) {
      statistics.value = statsRes.data.data
    }

    // Load rencana terbaru
    const plansRes = await axios.get('/api/ppic/production-plans?limit=5')
    if (plansRes.data.success) {
      recentPlans.value = plansRes.data.data || plansRes.data
    }

  } catch (error) {
    console.error('Gagal memuat data dashboard:', error)
  } finally {
    loading.value = false
  }
}

// Format angka
const formatNumber = (num) => {
  return new Intl.NumberFormat('id-ID').format(num)
}

// Format tanggal
const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

// Status badge
const getStatusBadge = (status) => {
  const statusConfig = {
    'draft': { class: 'bg-gray-100 text-gray-800', label: 'Draft' },
    'menunggu_persetujuan': { class: 'bg-yellow-100 text-yellow-800', label: 'Menunggu' },
    'disetujui': { class: 'bg-green-100 text-green-800', label: 'Disetujui' },
    'ditolak': { class: 'bg-red-100 text-red-800', label: 'Ditolak' },
    'menjadi_order': { class: 'bg-blue-100 text-blue-800', label: 'Menjadi Order' }
  }
  return statusConfig[status] || { class: 'bg-gray-100 text-gray-800', label: status }
}

// Progress berdasarkan status
const getProgress = (status) => {
  const progressMap = {
    'draft': 0,
    'menunggu_persetujuan': 50,
    'disetujui': 70,
    'menjadi_order': 100
  }
  return progressMap[status] || 0
}

// Quick actions berdasarkan role
const quickActions = computed(() => {
  const baseActions = [
    {
      title: 'Rencana Produksi',
      description: 'Buat dan kelola rencana produksi',
      icon: '📝',
      path: '/ppic/rencana-produksi',
      color: 'green'
    }
  ]

  // Hanya manager yang bisa akses laporan
  if (auth.user?.role === 'managerppic') {
    baseActions.push(
      {
        title: 'Master Produk',
        description: 'Kelola data produk perusahaan',
        icon: '📦',
        path: '/ppic/master-produk',
        color: 'blue'
      },
      {
        title: 'History Rencana',
        description: 'Lihat history perubahan rencana',
        icon: '📋',
        path: '/ppic/history-rencana',
        color: 'purple'
      },
      {
        title: 'Laporan Produksi',
        description: 'Generate laporan periodik',
        icon: '📊',
        path: '/ppic/laporan-produksi',
        color: 'orange'
      },
    )
  }

  return baseActions
})

// Refresh data
const refreshData = () => {
  loadDashboardData()
}

onMounted(() => {
  loadDashboardData()
})
</script>

<template>
  <PPICLayout>
    <div class="space-y-6">
      <!-- HEADER -->
      <div class="bg-white rounded-2xl shadow p-6">
        <div class="flex justify-between items-start mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard PPIC</h1>
            <p class="text-gray-600 mt-1">
              Selamat datang, <span class="font-semibold text-blue-600">{{ auth.user?.name }}</span>! 
              Anda login sebagai <span class="font-semibold capitalize">{{ auth.user?.role }}</span>.
            </p>
          </div>
          <button
            @click="refreshData"
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
          </button>
        </div>

        <!-- STATISTICS GRID -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <!-- Total Produk -->
          <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-blue-600 text-sm font-medium">Total Produk</p>
                <p class="text-2xl font-bold text-blue-800 mt-1">{{ formatNumber(statistics.totalProduk) }}</p>
              </div>
              <div class="text-blue-600 text-2xl">📦</div>
            </div>
          </div>

          <!-- Total Rencana -->
          <div class="bg-green-50 border border-green-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-green-600 text-sm font-medium">Total Rencana</p>
                <p class="text-2xl font-bold text-green-800 mt-1">{{ formatNumber(statistics.totalRencana) }}</p>
              </div>
              <div class="text-green-600 text-2xl">📝</div>
            </div>
          </div>

          <!-- Menunggu Persetujuan -->
          <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-yellow-600 text-sm font-medium">Menunggu Persetujuan</p>
                <p class="text-2xl font-bold text-yellow-800 mt-1">{{ formatNumber(statistics.rencanaMenunggu) }}</p>
              </div>
              <div class="text-yellow-600 text-2xl">⏳</div>
            </div>
          </div>

          <!-- Menjadi Order -->
          <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-purple-600 text-sm font-medium">Menjadi Order</p>
                <p class="text-2xl font-bold text-purple-800 mt-1">{{ formatNumber(statistics.rencanaMenjadiOrder) }}</p>
              </div>
              <div class="text-purple-600 text-2xl">✅</div>
            </div>
          </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="mb-8">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Aksi Cepat</h3>
          <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            <RouterLink
              v-for="action in quickActions"
              :key="action.path"
              :to="action.path"
              class="bg-white border-2 border-gray-200 rounded-xl p-4 hover:border-blue-500 hover:shadow-md transition-all duration-200 group"
            >
              <div class="flex items-center gap-3">
                <div 
                  class="text-2xl p-2 rounded-lg group-hover:scale-110 transition-transform"
                  :class="{
                    'bg-blue-100 text-blue-600': action.color === 'blue',
                    'bg-green-100 text-green-600': action.color === 'green',
                    'bg-purple-100 text-purple-600': action.color === 'purple',
                    'bg-orange-100 text-orange-600': action.color === 'orange'
                  }"
                >
                  {{ action.icon }}
                </div>
                <div>
                  <h4 class="font-semibold text-gray-800 group-hover:text-blue-600 transition-colors">
                    {{ action.title }}
                  </h4>
                  <p class="text-sm text-gray-600 mt-1">{{ action.description }}</p>
                </div>
              </div>
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- RENCANA PRODUKSI TERBARU -->
      <div class="bg-white rounded-2xl shadow p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-800">Rencana Produksi Terbaru</h3>
          <RouterLink 
            to="/ppic/rencana-produksi"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Buat Rencana Baru
          </RouterLink>
        </div>

        <div v-if="loading" class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p class="text-gray-500 mt-2">Memuat data rencana produksi...</p>
        </div>

        <div v-else-if="recentPlans.length === 0" class="text-center py-8">
          <div class="text-gray-400 text-4xl mb-3">📝</div>
          <h4 class="text-lg font-medium text-gray-900 mb-2">Belum ada rencana produksi</h4>
          <p class="text-gray-500 mb-4">Mulai dengan membuat rencana produksi pertama Anda</p>
          <RouterLink 
            to="/ppic/rencana-produksi"
            class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Buat Rencana Pertama
          </RouterLink>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="plan in recentPlans"
            :key="plan.id"
            class="border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200"
          >
            <div class="p-4">
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <span class="font-semibold text-gray-900 text-lg">{{ plan.nomor_rencana }}</span>
                    <span 
                      :class="['px-3 py-1 text-sm font-medium rounded-full capitalize', getStatusBadge(plan.status).class]"
                    >
                      {{ getStatusBadge(plan.status).label }}
                    </span>
                  </div>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div>
                      <span class="text-gray-500">Produk:</span>
                      <p class="font-medium text-gray-900">{{ plan.produk?.nama }}</p>
                      <p class="text-gray-600 text-xs">{{ plan.produk?.kode }}</p>
                    </div>
                    
                    <div>
                      <span class="text-gray-500">Jumlah:</span>
                      <p class="font-medium text-gray-900">
                        {{ formatNumber(plan.jumlah) }} {{ plan.produk?.satuan || 'pcs' }}
                      </p>
                    </div>
                    
                    <div>
                      <span class="text-gray-500">Batas Selesai:</span>
                      <p class="font-medium text-gray-900">{{ formatDate(plan.batas_selesai) }}</p>
                    </div>
                    
                    <div>
                      <span class="text-gray-500">Dibuat:</span>
                      <p class="font-medium text-gray-900">{{ formatDate(plan.created_at) }}</p>
                      <p class="text-gray-600 text-xs">oleh {{ plan.dibuat_oleh?.name || 'System' }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Progress Bar -->
              <div class="mt-4">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                  <span>Progress Persetujuan</span>
                  <span>{{ getProgress(plan.status) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    class="bg-gradient-to-r from-blue-500 to-green-500 h-2 rounded-full transition-all duration-500"
                    :style="{ width: `${getProgress(plan.status)}%` }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 rounded-b-lg">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">
                  Terakhir update: {{ formatDate(plan.updated_at) }}
                </span>
              </div>
            </div>
          </div>

          <!-- View All Link -->
          <div class="text-center pt-4 border-t border-gray-200">
            <RouterLink 
              to="/ppic/rencana-produksi"
              class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-medium"
            >
              Lihat Semua Rencana Produksi
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </PPICLayout>
</template>