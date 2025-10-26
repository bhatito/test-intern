<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import axios from 'axios'
import PPICLayout from '@/layouts/PPICLayout.vue'

const loading = ref(false)
const rencanaData = ref([])
const filterPeriode = ref('bulanan')
const filterTahun = ref(new Date().getFullYear())
const filterBulan = ref(new Date().getMonth() + 1)
const filterMinggu = ref(1)
const filterStatus = ref('')
const searchQuery = ref('')

const tahunList = computed(() => {
  const currentYear = new Date().getFullYear()
  return Array.from({ length: 5 }, (_, i) => currentYear - i)
})

const bulanList = [
  { value: 1, label: 'Januari' },
  { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' },
  { value: 4, label: 'April' },
  { value: 5, label: 'Mei' },
  { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' },
  { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' },
  { value: 12, label: 'Desember' }
]

const mingguList = Array.from({ length: 5 }, (_, i) => ({ value: i + 1, label: `Minggu ${i + 1}` }))

watch([filterPeriode, filterTahun, filterBulan, filterMinggu, filterStatus], () => {
  loadRencana()
})

// Load rencana produksi
const loadRencana = async () => {
  loading.value = true
  try {
    const params = {
      periode: filterPeriode.value,
      tahun: filterTahun.value
    }

    if (filterPeriode.value === 'bulanan') {
      params.bulan = filterBulan.value
    } else {
      params.minggu = filterMinggu.value
    }

    if (filterStatus.value) {
      params.status = filterStatus.value
    }

    const res = await axios.get('/api/ppic/production-reports', { params })
    
    rencanaData.value = res.data.data?.laporan || []
    
    console.log('Data rencana loaded:', rencanaData.value)
    
  } catch (error) {
    console.error('Gagal memuat rencana:', error)
    alert('Gagal memuat data rencana produksi')
  } finally {
    loading.value = false
  }
}

const generateLaporan = async () => {
  try {
    loading.value = true
    const params = {
      periode: filterPeriode.value,
      tahun: filterTahun.value
    }

    if (filterPeriode.value === 'bulanan') {
      params.bulan = filterBulan.value
    } else {
      params.minggu = filterMinggu.value
    }

    const res = await axios.post('/api/ppic/production-reports/generate', params)
    
    if (res.data.success) {
      alert('Laporan rencana berhasil digenerate!')
      await loadRencana()
    } else {
      alert('Gagal generate laporan: ' + res.data.message)
    }
  } catch (error) {
    console.error('Gagal generate laporan:', error)
    alert('Gagal generate laporan rencana produksi')
  } finally {
    loading.value = false
  }
}

const exportToExcel = async () => {
  try {
    loading.value = true
    
    const params = {
      periode: filterPeriode.value,
      tahun: filterTahun.value,
      format: 'excel'
    }

    if (filterPeriode.value === 'bulanan') {
      params.bulan = filterBulan.value
    } else {
      params.minggu = filterMinggu.value
    }

    if (filterStatus.value) {
      params.status = filterStatus.value
    }

    const res = await axios.get('/api/ppic/production-reports/export', { 
      params,
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    
    const bulanLabel = bulanList.find(b => b.value === filterBulan.value)?.label || ''
    const filename = `Laporan_Rencana_Produksi_${filterPeriode.value}_${filterTahun.value}_${filterPeriode.value === 'bulanan' ? bulanLabel : 'Minggu_' + filterMinggu.value}.xlsx`
    
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    setTimeout(() => {
      window.URL.revokeObjectURL(url)
    }, 100)
    
  } catch (error) {
    console.error('Gagal export laporan:', error)

    if (filteredRencana.value.length > 0) {
      exportFrontendExcel()
    } else {
      alert('Gagal export laporan ke Excel: ' + error.message)
    }
  } finally {
    loading.value = false
  }
}

const exportFrontendExcel = () => {
  try {
    const data = filteredRencana.value
    if (data.length === 0) {
      alert('Tidak ada data untuk diexport')
      return
    }

    let csvContent = "LAPORAN RENCANA PRODUKSI\n"
    csvContent += `Periode: ${activePeriodInfo.value}\n`
    csvContent += `Tanggal Export: ${new Date().toLocaleDateString('id-ID')}\n\n`

    const headers = [
      'No',
      'Nomor Rencana',
      'Produk',
      'Kode Produk',
      'Jumlah Rencana',
      'Satuan',
      'Status',
      'Progress',
      'Batas Selesai',
      'Keterlambatan',
      'Dibuat Oleh',
      'Disetujui Oleh',
      'Tanggal Dibuat'
    ]
    csvContent += headers.join(',') + '\n'

    data.forEach((item, index) => {
      const row = [
        index + 1,
        `"${item.nomor_rencana}"`,
        `"${item.produk?.nama || ''}"`,
        `"${item.produk?.kode || ''}"`,
        item.jumlah_rencana,
        `"${item.produk?.satuan || 'pcs'}"`,
        `"${getStatusBadge(item.status).label}"`,
        `${item.progress}%`,
        `"${formatDate(item.batas_selesai)}"`,
        `"${item.terlambat ? 'Ya' : 'Tidak'}"`,
        `"${item.dibuat_oleh}"`,
        `"${item.disetujui_oleh || '-'}"`,
        `"${formatDate(item.tanggal_dibuat)}"`
      ]
      csvContent += row.join(',') + '\n'
    })

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    const filename = `Laporan_Rencana_Produksi_${new Date().getTime()}.csv`
    
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    alert('Data berhasil diexport dalam format CSV')
    
  } catch (error) {
    console.error('Gagal export dari frontend:', error)
    alert('Gagal export data')
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatNumber = (number) => {
  if (!number) return '0'
  return number.toLocaleString('id-ID')
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

const getProgressBadge = (progress) => {
  if (progress >= 100) {
    return { class: 'bg-green-100 text-green-800', label: '100% Selesai' }
  } else if (progress >= 75) {
    return { class: 'bg-blue-100 text-blue-800', label: `${progress}%` }
  } else if (progress >= 50) {
    return { class: 'bg-yellow-100 text-yellow-800', label: `${progress}%` }
  } else if (progress >= 25) {
    return { class: 'bg-orange-100 text-orange-800', label: `${progress}%` }
  } else {
    return { class: 'bg-red-100 text-red-800', label: `${progress}%` }
  }
}

const filteredRencana = computed(() => {
  if (!rencanaData.value || !Array.isArray(rencanaData.value)) {
    return []
  }

  let filtered = rencanaData.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(rencana => 
      rencana.nomor_rencana?.toLowerCase().includes(query) ||
      rencana.produk?.nama?.toLowerCase().includes(query) ||
      rencana.produk?.kode?.toLowerCase().includes(query)
    )
  }

  return filtered
})

const statistics = computed(() => {
  const rencana = filteredRencana.value
  
  if (!Array.isArray(rencana) || rencana.length === 0) {
    return {
      total: 0,
      draft: 0,
      menunggu: 0,
      disetujui: 0,
      ditolak: 0,
      menjadi_order: 0
    }
  }

  return {
    total: rencana.length,
    draft: rencana.filter(r => r.status === 'draft').length,
    menunggu: rencana.filter(r => r.status === 'menunggu_persetujuan').length,
    disetujui: rencana.filter(r => r.status === 'disetujui').length,
    ditolak: rencana.filter(r => r.status === 'ditolak').length,
    menjadi_order: rencana.filter(r => r.status === 'menjadi_order').length
  }
})

const resetFilters = () => {
  filterPeriode.value = 'bulanan'
  filterTahun.value = new Date().getFullYear()
  filterBulan.value = new Date().getMonth() + 1
  filterMinggu.value = 1
  filterStatus.value = ''
  searchQuery.value = ''
}

const activePeriodInfo = computed(() => {
  if (filterPeriode.value === 'bulanan') {
    const bulan = bulanList.find(b => b.value === filterBulan.value)
    return `Periode Bulanan: ${bulan?.label} ${filterTahun.value}`
  } else {
    return `Periode Mingguan: Minggu ${filterMinggu.value} ${filterTahun.value}`
  }
})

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
            <h1 class="text-2xl font-bold text-gray-800">Laporan Rencana Produksi</h1>
            <p class="text-gray-600">Monitor dan kelola laporan rencana produksi secara periodik</p>
          </div>
          <div class="flex gap-3">
            <button 
              @click="exportToExcel" 
              :disabled="loading || filteredRencana.length === 0"
              class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              {{ loading ? 'Exporting...' : 'Export Excel' }}
            </button>
          </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span class="text-blue-800 font-medium">{{ activePeriodInfo }}</span>
              <span class="text-blue-600 text-sm">• Data akan otomatis reload ketika filter berubah</span>
            </div>
            <button 
              @click="loadRencana" 
              :disabled="loading"
              class="flex items-center gap-2 px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Refresh Manual
            </button>
          </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
          <div class="bg-blue-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ statistics.total }}</div>
            <div class="text-sm text-blue-800">Total Rencana</div>
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
          <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Cari Rencana</label>
              <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Cari nomor rencana atau produk..."
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
              <select 
                v-model="filterPeriode" 
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="mingguan">Mingguan</option>
                <option value="bulanan">Bulanan</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
              <select 
                v-model="filterTahun" 
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option v-for="tahun in tahunList" :key="tahun" :value="tahun">{{ tahun }}</option>
              </select>
            </div>
            <div v-if="filterPeriode === 'bulanan'">
              <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
              <select 
                v-model="filterBulan" 
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option v-for="bulan in bulanList" :key="bulan.value" :value="bulan.value">{{ bulan.label }}</option>
              </select>
            </div>
            <div v-if="filterPeriode === 'mingguan'">
              <label class="block text-sm font-medium text-gray-700 mb-1">Minggu</label>
              <select 
                v-model="filterMinggu" 
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option v-for="minggu in mingguList" :key="minggu.value" :value="minggu.value">{{ minggu.label }}</option>
              </select>
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status Rencana</label>
              <select 
                v-model="filterStatus" 
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Semua Status</option>
                <option value="draft">Draft</option>
                <option value="menunggu_persetujuan">Menunggu Persetujuan</option>
                <option value="disetujui">Disetujui</option>
                <option value="ditolak">Ditolak</option>
                <option value="menjadi_order">Menjadi Order</option>
              </select>
            </div>
            <div class="flex items-end">
              <button 
                @click="resetFilters"
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
            Detail Rencana Produksi
            <span class="ml-2 bg-blue-100 text-blue-800 text-sm px-2 py-1 rounded-full">
              {{ filteredRencana.length }} rencana
            </span>
          </h2>
        </div>

        <div class="p-6">
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-gray-500 mt-2">Memuat data rencana...</p>
          </div>

          <div v-else-if="!filteredRencana.length" class="text-center py-8">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada data rencana produksi</h3>
            <p class="text-gray-500">Tidak ditemukan rencana produksi yang sesuai dengan filter periode</p>
            <button 
              @click="generateLaporan"
              class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Generate Laporan
            </button>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Rencana</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batas Selesai</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat Oleh</th>
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
                    <div class="text-xs text-gray-500">{{ formatDate(rencana.tanggal_dibuat) }}</div>
                  </td>
                  <td class="px-4 py-4">
                    <div class="font-medium text-gray-900">{{ rencana.produk?.nama }}</div>
                    <div class="text-xs text-gray-500">{{ rencana.produk?.kode }}</div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <span class="font-medium text-gray-900">{{ formatNumber(rencana.jumlah_rencana) }}</span>
                    <span class="text-xs text-gray-500 ml-1">{{ rencana.produk?.satuan || 'pcs' }}</span>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <span 
                      :class="['px-3 py-1 text-xs font-medium rounded-full capitalize', getStatusBadge(rencana.status).class]"
                    >
                      {{ getStatusBadge(rencana.status).label }}
                    </span>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <div class="w-16 bg-gray-200 rounded-full h-2">
                        <div 
                          class="bg-blue-600 h-2 rounded-full transition-all" 
                          :style="{ width: `${rencana.progress || 0}%` }"
                        ></div>
                      </div>
                      <span 
                        :class="['px-2 py-1 text-xs font-medium rounded-full', getProgressBadge(rencana.progress || 0).class]"
                      >
                        {{ getProgressBadge(rencana.progress || 0).label }}
                      </span>
                    </div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div>{{ formatDate(rencana.batas_selesai) }}</div>
                    <div class="text-xs" :class="rencana.terlambat ? 'text-red-500' : 'text-green-500'">
                      {{ rencana.terlambat ? 'Terlambat' : 'Tepat Waktu' }}
                    </div>
                  </td>
                  <td class="px-4 py-4">
                    <div class="text-sm text-gray-700">{{ rencana.dibuat_oleh }}</div>
                    <div v-if="rencana.disetujui_oleh" class="text-xs text-gray-500">
                      Disetujui: {{ rencana.disetujui_oleh }}
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </PPICLayout>
</template>