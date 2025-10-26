<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import ProduksiLayout from '@/layouts/ProduksiLayout.vue'

const loading = ref(false)
const orders = ref([])
const filter = ref('')

const loadOrders = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/produksi/production-orders', {
      params: { status: filter.value || undefined }
    })
    orders.value = res.data.data || []
  } catch (error) {
    console.error('Gagal memuat orders:', error)
    alert('Gagal memuat data order produksi')
  } finally {
    loading.value = false
  }
}

const startOrder = async (order) => {
  if (!confirm(`Mulai produksi untuk order ${order.nomor_order}?`)) return
  
  try {
    const response = await axios.put(`/api/produksi/production-orders/${order.id}/start`)
    
    if (response.data.success) {
      alert('Order produksi berhasil dimulai')
      await loadOrders()
    } else {
      alert(response.data.message || 'Gagal memulai order')
    }
  } catch (error) {
    const message = error.response?.data?.message || 'Terjadi kesalahan sistem'
    alert(`Gagal memulai order: ${message}`)
    console.error('Error starting order:', error)
  }
}

const completeOrder = async (order) => {
  const jumlah_aktual = parseInt(prompt('Masukkan jumlah aktual yang berhasil diproduksi:', order.target_jumlah ?? 0) || '0', 10)
  if (isNaN(jumlah_aktual) || jumlah_aktual < 0) {
    alert('Jumlah aktual harus angka positif')
    return
  }

  const jumlah_reject = parseInt(prompt('Masukkan jumlah reject (NG/rusak):', '0') || '0', 10)
  if (isNaN(jumlah_reject) || jumlah_reject < 0) {
    alert('Jumlah reject harus angka positif')
    return
  }

  if (jumlah_reject > jumlah_aktual) {
    alert('Jumlah reject tidak boleh melebihi jumlah aktual')
    return
  }

  const jumlah_good = jumlah_aktual - jumlah_reject

  const confirmationMessage = `
Konfirmasi penyelesaian order:
- Jumlah Aktual: ${jumlah_aktual.toLocaleString()} ${order.produk?.satuan || 'pcs'}
- Jumlah Good: ${jumlah_good.toLocaleString()} ${order.produk?.satuan || 'pcs'} 
- Jumlah Reject: ${jumlah_reject.toLocaleString()} ${order.produk?.satuan || 'pcs'}

Lanjutkan penyelesaian order?`

  if (!confirm(confirmationMessage)) return

  const catatan = prompt('Catatan produksi (opsional):') || undefined

  try {
    const response = await axios.put(`/api/produksi/production-orders/${order.id}/complete`, {
      jumlah_aktual, 
      jumlah_reject, 
      catatan
    })
    
    if (response.data.success) {
      alert('Order produksi berhasil diselesaikan!')
      await loadOrders()
    } else {
      alert(response.data.message || 'Gagal menyelesaikan order')
    }
  } catch (error) {
    const message = error.response?.data?.message || 'Terjadi kesalahan sistem'
    alert(`Gagal menyelesaikan order: ${message}`)
    console.error('Error completing order:', error)
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

const getStatusColor = (status) => {
  const colors = {
    'menunggu': 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'dalam_proses': 'bg-blue-100 text-blue-800 border-blue-200',
    'selesai': 'bg-green-100 text-green-800 border-green-200',
    'dikerjakan': 'bg-blue-100 text-blue-800 border-blue-200',
  }
  return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200'
}

const getStatusLabel = (status) => {
  const labels = {
    'menunggu': 'Menunggu',
    'dalam_proses': 'Dalam Proses',
    'selesai': 'Selesai',
    'dikerjakan': 'Dalam Proses', 
  }
  return labels[status] || status
}

onMounted(loadOrders)
</script>

<template>
  <ProduksiLayout>
    <div class="bg-white rounded-2xl shadow p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Order Produksi</h2>
          <p class="text-gray-600 text-sm">Kelola order produksi yang sedang berjalan</p>
        </div>
        <div class="flex items-center gap-2">
          <select v-model="filter" @change="loadOrders" class="border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">Semua Status</option>
            <option value="menunggu">Menunggu</option>
            <option value="dalam_proses">Dalam Proses</option>
            <option value="selesai">Selesai</option>
          </select>
          <button 
            @click="loadOrders" 
            :disabled="loading"
            class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            {{ loading ? 'Loading...' : 'Refresh' }}
          </button>
        </div>
      </div>

      <div v-if="loading" class="text-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="text-gray-500 mt-2">Memuat data order...</p>
      </div>

      <div v-else-if="!orders.length" class="text-center py-8">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada order produksi</h3>
        <p class="text-gray-500">Semua order produksi telah diproses atau belum ada order baru</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Order</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mulai Produksi</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selesai</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="font-medium text-gray-900">{{ order.nomor_order }}</div>
                <div class="text-xs text-gray-500">{{ formatDate(order.created_at) }}</div>
              </td>
              <td class="px-4 py-4">
                <div class="font-medium text-gray-900">{{ order.produk?.nama }}</div>
                <div class="text-xs text-gray-500">{{ order.produk?.kode }}</div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <span class="font-medium text-gray-900">{{ order.target_jumlah?.toLocaleString() }}</span>
                <span class="text-xs text-gray-500 ml-1">{{ order.produk?.satuan || 'pcs' }}</span>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <span :class="['px-3 py-1 text-xs font-medium rounded-full border capitalize', getStatusColor(order.status)]">
                  {{ getStatusLabel(order.status) }}
                </span>
                <div v-if="order.status === 'dalam_proses' && order.mulai_pada" class="text-xs text-gray-500 mt-1">
                  {{ formatTimeAgo(order.mulai_pada) }}
                </div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(order.mulai_pada) }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(order.selesai_pada) }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="flex gap-2">
                  <button
                    v-if="order.status === 'menunggu'"
                    @click="startOrder(order)"
                    class="px-3 py-1 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-1"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Mulai
                  </button>

                  <button
                    v-if="order.status === 'dalam_proses' || order.status === 'dikerjakan'"
                    @click="completeOrder(order)"
                    class="px-3 py-1 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Selesai
                  </button>

                  <span v-if="order.status === 'selesai'" class="text-xs text-gray-500 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Selesai
                  </span>
                </div>

                <div v-if="order.status === 'selesai' && order.jumlah_aktual" class="text-xs text-gray-600 mt-1">
                  Aktual: {{ order.jumlah_aktual?.toLocaleString() }}
                  <span v-if="order.jumlah_reject" class="text-red-600">
                    (Reject: {{ order.jumlah_reject?.toLocaleString() }})
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ProduksiLayout>
</template>