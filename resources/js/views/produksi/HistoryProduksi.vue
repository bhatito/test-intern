<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import ProduksiLayout from '@/layouts/ProduksiLayout.vue'

const loading = ref(false)
const ordersList = ref([])
const selectedOrder = ref(null)
const orderHistory = ref([])
const showHistoryModal = ref(false)
const statistics = ref({
  total: 0,
  menunggu: 0,
  dalam_proses: 0,
  selesai: 0
})

const filters = ref({
  status: '',
  start_date: '',
  end_date: '',
  search: ''
})

const loadOrders = async () => {
  loading.value = true
  try {
    const params = {}
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.start_date) params.start_date = filters.value.start_date
    if (filters.value.end_date) params.end_date = filters.value.end_date
    if (filters.value.search) params.search = filters.value.search
    
    const res = await axios.get('/api/produksi/orders', { params })
    ordersList.value = res.data.data?.data || []
  } catch (error) {
    console.error('Gagal memuat orders:', error)
    alert('Gagal memuat data order produksi: ' + (error.response?.data?.message || 'Server error'))
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    const res = await axios.get('/api/produksi/orders/statistics')
    statistics.value = res.data.data || {
      total: 0,
      menunggu: 0,
      dalam_proses: 0,
      selesai: 0
    }
  } catch (error) {
    console.error('Gagal memuat statistik:', error)
    statistics.value = {
      total: 0,
      menunggu: 0,
      dalam_proses: 0,
      selesai: 0
    }
  }
}

const loadOrderHistory = async (order) => {
  selectedOrder.value = order
  try {
    const res = await axios.get(`/api/produksi/order-history/${order.id}`)
    orderHistory.value = res.data.data?.histories || []
    showHistoryModal.value = true
  } catch (error) {
    console.error('Gagal memuat history:', error)
    alert('Gagal memuat history order: ' + (error.response?.data?.message || 'Server error'))
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatTimeAgo = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'Baru saja'
  if (diffMins < 60) return `${diffMins} menit lalu`
  if (diffHours < 24) return `${diffHours} jam lalu`
  if (diffDays === 1) return 'Kemarin'
  if (diffDays < 7) return `${diffDays} hari lalu`
  return formatDate(dateString)
}

const getStatusBadge = (status) => {
  const statusConfig = {
    'menunggu': { class: 'bg-yellow-100 text-yellow-800', label: 'Menunggu', icon: '⏳' },
    'dalam_proses': { class: 'bg-blue-100 text-blue-800', label: 'Dalam Proses', icon: '⚙️' },
    'selesai': { class: 'bg-green-100 text-green-800', label: 'Selesai', icon: '✅' }
  }
  return statusConfig[status] || { class: 'bg-gray-100 text-gray-800', label: status, icon: '📋' }
}

const getActionBadge = (statusBaru) => {
  const actionConfig = {
    'menunggu': { class: 'bg-yellow-100 text-yellow-800', label: 'Dibuat', icon: '📝' },
    'dalam_proses': { class: 'bg-blue-100 text-blue-800', label: 'Diproses', icon: '⚙️' },
    'selesai': { class: 'bg-green-100 text-green-800', label: 'Diselesaikan', icon: '✅' }
  }
  return actionConfig[statusBaru] || { class: 'bg-gray-100 text-gray-800', label: 'Diubah', icon: '✏️' }
}

const formatNumber = (number) => {
  return number ? number.toLocaleString('id-ID') : '0'
}

const calculateProgress = (order) => {
  if (!order.jumlah_aktual || !order.target_jumlah) return 0
  return Math.min(Math.round((order.jumlah_aktual / order.target_jumlah) * 100), 100)
}

const applyFilters = () => {
  loadOrders()
  loadStatistics()
}

const resetFilters = () => {
  filters.value = {
    status: '',
    start_date: '',
    end_date: '',
    search: ''
  }
  loadOrders()
  loadStatistics()
}

const filteredOrders = computed(() => {
  return ordersList.value
})

onMounted(() => {
  loadOrders()
  loadStatistics()
})
</script>

<template>
  <ProduksiLayout>
    <div class="space-y-6">
      <div class="bg-white rounded-xl shadow p-6">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">History Order Produksi</h1>
            <p class="text-gray-600">Monitor seluruh aktivitas dan riwayat perubahan order produksi</p>
          </div>
          <button 
            @click="loadOrders" 
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
          </button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-blue-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ statistics.total }}</div>
            <div class="text-sm text-blue-800">Total Order</div>
          </div>
          <div class="bg-yellow-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ statistics.menunggu }}</div>
            <div class="text-sm text-yellow-800">Menunggu</div>
          </div>
          <div class="bg-blue-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ statistics.dalam_proses }}</div>
            <div class="text-sm text-blue-800">Dalam Proses</div>
          </div>
          <div class="bg-green-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ statistics.selesai }}</div>
            <div class="text-sm text-green-800">Selesai</div>
          </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4 mb-6">
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select 
                v-model="filters.status"
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
              >
                <option value="">Semua Status</option>
                <option value="menunggu">Menunggu</option>
                <option value="dalam_proses">Dalam Proses</option>
                <option value="selesai">Selesai</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
              <input 
                v-model="filters.start_date"
                type="date"
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
              <input 
                v-model="filters.end_date"
                type="date"
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Cari Nomor Order</label>
              <input 
                v-model="filters.search" 
                type="text" 
                placeholder="Masukkan nomor order..."
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
              />
            </div>
            <div class="flex items-end gap-2">
              <button 
                @click="applyFilters"
                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
              >
                Terapkan
              </button>
              <button 
                @click="resetFilters"
                class="w-full px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
              >
                Reset
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-800">
            Daftar Order Produksi
            <span class="ml-2 bg-green-100 text-green-800 text-sm px-2 py-1 rounded-full">
              {{ filteredOrders.length }} items
            </span>
          </h2>
        </div>

        <div class="p-6">
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
            <p class="text-gray-500 mt-2">Memuat data order produksi...</p>
          </div>

          <div v-else-if="!filteredOrders.length" class="text-center py-8">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada data order produksi</h3>
            <p class="text-gray-500" v-if="filters.search">
              Tidak ditemukan order dengan nomor "{{ filters.search }}"
            </p>
            <p class="text-gray-500" v-else>
              Belum ada order produksi yang dibuat
            </p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Order</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target / Aktual</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timeline</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr 
                  v-for="order in filteredOrders" 
                  :key="order.id" 
                  class="hover:bg-gray-50 transition-colors"
                >
                  <td class="px-4 py-4 whitespace-nowrap">
                    <div class="font-medium text-gray-900">{{ order.nomor_order }}</div>
                    <div class="text-xs text-gray-500">ID: {{ order.id?.slice(-8) }}</div>
                  </td>
                  <td class="px-4 py-4">
                    <div class="font-medium text-gray-900">{{ order.produk?.nama || 'N/A' }}</div>
                    <div class="text-xs text-gray-500">{{ order.produk?.kode || '' }}</div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <div class="text-sm">
                      <span class="font-medium text-gray-900">{{ formatNumber(order.target_jumlah) }}</span>
                      <span class="text-gray-500 text-xs ml-1">{{ order.produk?.satuan || 'pcs' }}</span>
                    </div>
                    <div class="text-xs text-gray-500" v-if="order.jumlah_aktual">
                      Aktual: {{ formatNumber(order.jumlah_aktual) }}
                      <span v-if="order.jumlah_reject" class="text-red-500">
                        (Reject: {{ formatNumber(order.jumlah_reject) }})
                      </span>
                    </div>
                  </td>
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-2">
                      <div class="w-16 bg-gray-200 rounded-full h-2">
                        <div 
                          class="bg-green-600 h-2 rounded-full transition-all"
                          :style="{ width: calculateProgress(order) + '%' }"
                        ></div>
                      </div>
                      <span class="text-xs text-gray-600">{{ calculateProgress(order) }}%</span>
                    </div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <span 
                      :class="['px-3 py-1 text-xs font-medium rounded-full capitalize flex items-center gap-1 w-fit', getStatusBadge(order.status).class]"
                    >
                      {{ getStatusBadge(order.status).icon }}
                      {{ getStatusBadge(order.status).label }}
                    </span>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div v-if="order.mulai_pada">
                      <div class="font-medium">Mulai:</div>
                      <div>{{ formatDate(order.mulai_pada) }}</div>
                    </div>
                    <div v-if="order.selesai_pada" class="mt-1">
                      <div class="font-medium">Selesai:</div>
                      <div>{{ formatDate(order.selesai_pada) }}</div>
                    </div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <button
                      @click="loadOrderHistory(order)"
                      class="flex items-center gap-2 px-3 py-1 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition-colors"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                      </svg>
                      Lihat History
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showHistoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200">
          <div class="flex justify-between items-start">
            <div>
              <h3 class="text-lg font-semibold text-gray-800">History Order Produksi</h3>
              <p class="text-gray-600" v-if="selectedOrder">
                {{ selectedOrder.nomor_order }} - {{ selectedOrder.produk?.nama || 'N/A' }}
                <span class="ml-2 text-sm px-2 py-1 rounded-full" :class="getStatusBadge(selectedOrder.status).class">
                  {{ getStatusBadge(selectedOrder.status).label }}
                </span>
              </p>
            </div>
            <button
              @click="showHistoryModal = false"
              class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
          <div v-if="orderHistory.length === 0" class="text-center py-8">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-gray-500">Tidak ada history untuk order ini</p>
          </div>

          <div v-else class="space-y-4">
            <div 
              v-for="history in orderHistory" 
              :key="history.id"
              class="border-l-4 pl-4 py-3"
              :class="{
                'border-yellow-500': history.status_baru === 'menunggu',
                'border-blue-500': history.status_baru === 'dalam_proses',
                'border-green-500': history.status_baru === 'selesai'
              }"
            >
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span 
                      :class="['px-2 py-1 text-xs font-medium rounded-full capitalize flex items-center gap-1', getActionBadge(history.status_baru).class]"
                    >
                      {{ getActionBadge(history.status_baru).icon }}
                      {{ getActionBadge(history.status_baru).label }}
                    </span>
                    <span class="text-xs text-gray-500">
                      oleh {{ history.changed_by?.name || 'System' }}
                    </span>
                  </div>
                  <p class="text-sm text-gray-700 mb-1">{{ history.keterangan || 'Perubahan status order' }}</p>
                  <div v-if="history.status_sebelumnya && history.status_baru" class="text-xs text-gray-500">
                    Status: 
                    <span class="font-medium capitalize">{{ (history.status_sebelumnya || 'baru')?.replace('_', ' ') }}</span> 
                    → 
                    <span class="font-medium capitalize">{{ history.status_baru?.replace('_', ' ') }}</span>
                  </div>
                </div>
                <div class="text-right text-xs text-gray-500 whitespace-nowrap ml-4">
                  <div>{{ formatDate(history.diubah_pada) }}</div>
                  <div>{{ formatTimeAgo(history.diubah_pada) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ProduksiLayout>
</template>