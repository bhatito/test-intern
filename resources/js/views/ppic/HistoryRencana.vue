<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import PPICLayout from '@/layouts/PPICLayout.vue'

const loading = ref(false)
const rencanaList = ref([])
const selectedPlan = ref(null)
const planHistory = ref([])
const showHistoryModal = ref(false)

const searchQuery = ref('')

const loadRencana = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/ppic/production-plans')
    rencanaList.value = res.data.data || []
  } catch (error) {
    console.error('Gagal memuat rencana:', error)
    alert('Gagal memuat data rencana produksi')
  } finally {
    loading.value = false
  }
}

const loadPlanHistory = async (plan) => {
  selectedPlan.value = plan
  try {
    const res = await axios.get(`/api/ppic/production-plans/${plan.id}/history`)
    planHistory.value = res.data.data || []
    showHistoryModal.value = true
  } catch (error) {
    console.error('Gagal memuat history:', error)
    alert('Gagal memuat history rencana')
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
    'draft': { class: 'bg-gray-100 text-gray-800', label: 'Draft' },
    'menunggu_persetujuan': { class: 'bg-yellow-100 text-yellow-800', label: 'Menunggu Persetujuan' },
    'disetujui': { class: 'bg-green-100 text-green-800', label: 'Disetujui' },
    'ditolak': { class: 'bg-red-100 text-red-800', label: 'Ditolak' },
    'menjadi_order': { class: 'bg-blue-100 text-blue-800', label: 'Menjadi Order' }
  }
  return statusConfig[status] || { class: 'bg-gray-100 text-gray-800', label: status }
}

const getActionBadge = (aksi) => {
  const actionConfig = {
    'dibuat': { class: 'bg-blue-100 text-blue-800', label: 'Dibuat', icon: '📝' },
    'diajukan': { class: 'bg-yellow-100 text-yellow-800', label: 'Diajukan', icon: '📤' },
    'disetujui': { class: 'bg-green-100 text-green-800', label: 'Disetujui', icon: '✅' },
    'ditolak': { class: 'bg-red-100 text-red-800', label: 'Ditolak', icon: '❌' },
    'diproses': { class: 'bg-purple-100 text-purple-800', label: 'Diproses', icon: '⚙️' },
    'diupdate': { class: 'bg-orange-100 text-orange-800', label: 'Diupdate', icon: '✏️' },
    'dibatalkan': { class: 'bg-gray-100 text-gray-800', label: 'Dibatalkan', icon: '↩️' },
    'dihapus': { class: 'bg-red-100 text-red-800', label: 'Dihapus', icon: '🗑️' }
  }
  return actionConfig[aksi] || { class: 'bg-gray-100 text-gray-800', label: aksi, icon: '📋' }
}

const filteredRencana = computed(() => {
  if (!searchQuery.value) {
    return rencanaList.value
  }

  const query = searchQuery.value.toLowerCase()
  return rencanaList.value.filter(rencana => 
    rencana.nomor_rencana.toLowerCase().includes(query)
  )
})

const statistics = computed(() => {
  return {
    total: rencanaList.value.length,
    draft: rencanaList.value.filter(r => r.status === 'draft').length,
    menunggu: rencanaList.value.filter(r => r.status === 'menunggu_persetujuan').length,
    disetujui: rencanaList.value.filter(r => r.status === 'disetujui').length,
    ditolak: rencanaList.value.filter(r => r.status === 'ditolak').length,
    menjadi_order: rencanaList.value.filter(r => r.status === 'menjadi_order').length
  }
})

const resetFilter = () => {
  searchQuery.value = ''
}

onMounted(() => {
  loadRencana()
})
</script>

<template>
  <PPICLayout>
    <div class="space-y-6">
      <div class="bg-white rounded-xl shadow p-6">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">History Rencana Produksi</h1>
            <p class="text-gray-600">Monitor seluruh aktivitas dan riwayat perubahan rencana produksi</p>
          </div>
          <button 
            @click="loadRencana" 
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
          </button>
        </div>

      
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
          <div class="bg-blue-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ statistics.total }}</div>
            <div class="text-sm text-blue-800">Total</div>
          </div>
          <div class="bg-gray-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ statistics.draft }}</div>
            <div class="text-sm text-gray-800">Draft</div>
          </div>
          <div class="bg-yellow-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ statistics.menunggu }}</div>
            <div class="text-sm text-yellow-800">Menunggu</div>
          </div>
          <div class="bg-green-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ statistics.disetujui }}</div>
            <div class="text-sm text-green-800">Disetujui</div>
          </div>
          <div class="bg-red-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ statistics.ditolak }}</div>
            <div class="text-sm text-red-800">Ditolak</div>
          </div>
          <div class="bg-indigo-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-indigo-600">{{ statistics.menjadi_order }}</div>
            <div class="text-sm text-indigo-800">Menjadi Order</div>
          </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4 mb-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Cari Nomor Rencana</label>
              <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Masukkan nomor rencana..."
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div class="flex items-end">
              <button 
                @click="resetFilter"
                class="w-full px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
              >
                Reset Filter
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-800">
            Daftar Rencana Produksi
            <span class="ml-2 bg-blue-100 text-blue-800 text-sm px-2 py-1 rounded-full">
              {{ filteredRencana.length }} items
            </span>
          </h2>
        </div>

        <div class="p-6">
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-gray-500 mt-2">Memuat data history...</p>
          </div>

          <div v-else-if="!filteredRencana.length" class="text-center py-8">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada data rencana produksi</h3>
            <p class="text-gray-500" v-if="searchQuery">
              Tidak ditemukan rencana dengan nomor "{{ searchQuery }}"
            </p>
            <p class="text-gray-500" v-else>
              Belum ada rencana produksi yang dibuat
            </p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Rencana</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr 
                  v-for="rencana in filteredRencana" 
                  :key="rencana.id" 
                  class="hover:bg-gray-50 transition-colors"
                >
                  <td class="px-4 py-4 whitespace-nowrap">
                    <div class="font-medium text-gray-900">{{ rencana.nomor_rencana }}</div>
                    <div class="text-xs text-gray-500">ID: {{ rencana.id.slice(-8) }}</div>
                  </td>
                  <td class="px-4 py-4">
                    <div class="font-medium text-gray-900">{{ rencana.produk?.nama }}</div>
                    <div class="text-xs text-gray-500">{{ rencana.produk?.kode }}</div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <span class="font-medium text-gray-900">{{ rencana.jumlah?.toLocaleString() }}</span>
                    <span class="text-xs text-gray-500 ml-1">{{ rencana.produk?.satuan || 'pcs' }}</span>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <span 
                      :class="['px-3 py-1 text-xs font-medium rounded-full capitalize', getStatusBadge(rencana.status).class]"
                    >
                      {{ getStatusBadge(rencana.status).label }}
                    </span>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div>{{ formatDate(rencana.created_at) }}</div>
                    <div class="text-xs text-gray-400">{{ formatTimeAgo(rencana.created_at) }}</div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <button
                      @click="loadPlanHistory(rencana)"
                      class="flex items-center gap-2 px-3 py-1 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 transition-colors"
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
              <h3 class="text-lg font-semibold text-gray-800">History Rencana Produksi</h3>
              <p class="text-gray-600" v-if="selectedPlan">
                {{ selectedPlan.nomor_rencana }} - {{ selectedPlan.produk?.nama }}
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
          <div v-if="planHistory.length === 0" class="text-center py-8">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-gray-500">Tidak ada history untuk rencana ini</p>
          </div>

          <div v-else class="space-y-4">
            <div 
              v-for="history in planHistory" 
              :key="history.id"
              class="border-l-4 pl-4 py-3"
              :class="{
                'border-blue-500': history.aksi === 'dibuat',
                'border-yellow-500': history.aksi === 'diajukan',
                'border-green-500': history.aksi === 'disetujui',
                'border-red-500': history.aksi === 'ditolak',
                'border-purple-500': history.aksi === 'diproses',
                'border-orange-500': history.aksi === 'diupdate',
                'border-gray-500': history.aksi === 'dibatalkan' || history.aksi === 'dihapus'
              }"
            >
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span 
                      :class="['px-2 py-1 text-xs font-medium rounded-full capitalize', getActionBadge(history.aksi).class]"
                    >
                      {{ getActionBadge(history.aksi).icon }} {{ getActionBadge(history.aksi).label }}
                    </span>
                    <span class="text-xs text-gray-500">
                      oleh {{ history.user?.name }}
                    </span>
                  </div>
                  <p class="text-sm text-gray-700 mb-1">{{ history.keterangan }}</p>
                  <div v-if="history.status_sebelum && history.status_baru" class="text-xs text-gray-500">
                    Status: 
                    <span class="font-medium">{{ history.status_sebelum }}</span> 
                    → 
                    <span class="font-medium">{{ history.status_baru }}</span>
                  </div>
                </div>
                <div class="text-right text-xs text-gray-500 whitespace-nowrap ml-4">
                  <div>{{ formatDate(history.waktu_aksi) }}</div>
                  <div>{{ formatTimeAgo(history.waktu_aksi) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PPICLayout>
</template>