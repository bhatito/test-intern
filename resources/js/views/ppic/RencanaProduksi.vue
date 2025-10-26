<script setup>
// The script section remains unchanged
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import PPICLayout from '@/layouts/PPICLayout.vue'

const produkList = ref([])
const rencanaList = ref([])
const form = ref({ 
  produk_id: '', 
  jumlah: '', 
  batas_selesai: '',
  catatan: '' 
})
const loading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const selectedStatus = ref('')

// 🔹 Ambil daftar produk dari master produk
const loadProduk = async () => {
  try {
    const res = await axios.get('/api/ppic/master-products')
    produkList.value = res.data.data || res.data
  } catch (err) {
    console.error('Gagal memuat produk:', err)
  }
}

// 🔹 Ambil daftar rencana produksi
const loadRencana = async () => {
  loading.value = true
  try {
    const params = selectedStatus.value ? { status: selectedStatus.value } : {}
    const res = await axios.get('/api/ppic/production-plans', { params })
    rencanaList.value = res.data.data || res.data
  } catch (err) {
    console.error('Gagal memuat rencana:', err)
    errorMsg.value = 'Gagal memuat data rencana produksi'
  } finally {
    loading.value = false
  }
}

// 🔹 Simpan rencana baru
const submitForm = async () => {
  errorMsg.value = ''
  successMsg.value = ''
  
  // Validasi client-side
  if (!form.value.produk_id || !form.value.jumlah || !form.value.batas_selesai) {
    errorMsg.value = 'Harap lengkapi semua field yang wajib diisi'
    return
  }

  if (form.value.jumlah < 1) {
    errorMsg.value = 'Jumlah harus lebih dari 0'
    return
  }

  const today = new Date().toISOString().split('T')[0]
  if (form.value.batas_selesai <= today) {
    errorMsg.value = 'Batas selesai harus lebih dari hari ini'
    return
  }

  try {
    loading.value = true
    await axios.post('/api/ppic/production-plans', form.value)
    
    // Reset form
    form.value = { produk_id: '', jumlah: '', batas_selesai: '', catatan: '' }
    successMsg.value = 'Rencana produksi berhasil dibuat dan menunggu persetujuan Manager Produksi'
    
    // Reload data
    await loadRencana()
    
    // Hilangkan pesan sukses setelah 5 detik
    setTimeout(() => {
      successMsg.value = ''
    }, 5000)
    
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Terjadi kesalahan saat menyimpan rencana.'
  } finally {
    loading.value = false
  }
}

// 🔹 Hapus rencana (jika masih draft atau menunggu persetujuan)
const deleteRencana = async (rencana) => {
  if (!confirm(`Yakin menghapus rencana ${rencana.nomor_rencana}?`)) return
  
  try {
    await axios.delete(`/api/ppic/production-plans/${rencana.id}`)
    successMsg.value = 'Rencana produksi berhasil dihapus'
    await loadRencana()
    
    setTimeout(() => {
      successMsg.value = ''
    }, 3000)
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Gagal menghapus rencana.'
  }
}

// 🔹 Ajukan rencana (dari draft ke menunggu persetujuan)
const submitRencana = async (rencana) => {
  if (!confirm(`Ajukan rencana ${rencana.nomor_rencana} untuk persetujuan Manager Produksi?`)) return
  
  try {
    await axios.put(`/api/ppic/production-plans/${rencana.id}/submit`)
    successMsg.value = 'Rencana berhasil diajukan untuk persetujuan'
    await loadRencana()
    
    setTimeout(() => {
      successMsg.value = ''
    }, 3000)
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Gagal mengajukan rencana.'
  }
}

// 🔹 Batalkan pengajuan (dari menunggu persetujuan ke draft)
const cancelSubmission = async (rencana) => {
  if (!confirm(`Batalkan pengajuan rencana ${rencana.nomor_rencana}?`)) return
  
  try {
    await axios.put(`/api/ppic/production-plans/${rencana.id}/cancel`)
    successMsg.value = 'Pengajuan rencana berhasil dibatalkan'
    await loadRencana()
    
    setTimeout(() => {
      successMsg.value = ''
    }, 3000)
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Gagal membatalkan pengajuan.'
  }
}

// 🔹 Format tanggal untuk display
const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

// 🔹 Status badge dengan warna yang sesuai
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

// 🔹 Filter rencana berdasarkan status
const filteredRencana = computed(() => {
  if (!selectedStatus.value) return rencanaList.value
  return rencanaList.value.filter(rencana => rencana.status === selectedStatus.value)
})

// 🔹 Hitung statistik
const statistics = computed(() => {
  const stats = {
    total: rencanaList.value.length,
    draft: rencanaList.value.filter(r => r.status === 'draft').length,
    menunggu: rencanaList.value.filter(r => r.status === 'menunggu_persetujuan').length,
    disetujui: rencanaList.value.filter(r => r.status === 'disetujui').length,
    ditolak: rencanaList.value.filter(r => r.status === 'ditolak').length,
    menjadi_order: rencanaList.value.filter(r => r.status === 'menjadi_order').length
  }
  return stats
})

// 🔹 Tentukan tombol aksi berdasarkan status
const getActionButtons = (rencana) => {
  const actions = []
  
  if (rencana.status === 'draft') {
    actions.push({
      label: 'Ajukan',
      action: () => submitRencana(rencana),
      class: 'bg-blue-600 text-white hover:bg-blue-700',
      icon: '📤'
    })
    actions.push({
      label: 'Hapus',
      action: () => deleteRencana(rencana),
      class: 'bg-red-600 text-white hover:bg-red-700',
      icon: '🗑️'
    })
  }
  
  if (rencana.status === 'menunggu_persetujuan') {
    actions.push({
      label: 'Batalkan',
      action: () => cancelSubmission(rencana),
      class: 'bg-orange-600 text-white hover:bg-orange-700',
      icon: '↩️'
    })
    actions.push({
      label: 'Hapus',
      action: () => deleteRencana(rencana),
      class: 'bg-red-600 text-white hover:bg-red-700',
      icon: '🗑️'
    })
  }
  
  if (rencana.status === 'ditolak') {
    actions.push({
      label: 'Hapus',
      action: () => deleteRencana(rencana),
      class: 'bg-red-600 text-white hover:bg-red-700',
      icon: '🗑️'
    })
  }
  
  return actions
}

onMounted(() => {
  loadProduk()
  loadRencana()
})
</script>

<template>
  <PPICLayout>
    <div class="space-y-6 px-4 sm:px-6">
      <!-- HEADER -->
      <div class="bg-white rounded-xl shadow p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Rencana Produksi</h1>
            <p class="text-sm sm:text-base text-gray-600">Kelola rencana produksi dan monitoring status persetujuan</p>
          </div>
        </div>

        <!-- STATISTICS -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 sm:gap-4 mb-6">
          <div class="bg-blue-50 rounded-lg p-3 sm:p-4 text-center">
            <div class="text-xl sm:text-2xl font-bold text-blue-600">{{ statistics.total }}</div>
            <div class="text-xs sm:text-sm text-blue-800">Total</div>
          </div>
          <div class="bg-gray-50 rounded-lg p-3 sm:p-4 text-center">
            <div class="text-xl sm:text-2xl font-bold text-gray-600">{{ statistics.draft }}</div>
            <div class="text-xs sm:text-sm text-gray-800">Draft</div>
          </div>
          <div class="bg-yellow-50 rounded-lg p-3 sm:p-4 text-center">
            <div class="text-xl sm:text-2xl font-bold text-yellow-600">{{ statistics.menunggu }}</div>
            <div class="text-xs sm:text-sm text-yellow-800">Menunggu</div>
          </div>
          <div class="bg-green-50 rounded-lg p-3 sm:p-4 text-center">
            <div class="text-xl sm:text-2xl font-bold text-green-600">{{ statistics.disetujui }}</div>
            <div class="text-xs sm:text-sm text-green-800">Disetujui</div>
          </div>
          <div class="bg-red-50 rounded-lg p-3 sm:p-4 text-center">
            <div class="text-xl sm:text-2xl font-bold text-red-600">{{ statistics.ditolak }}</div>
            <div class="text-xs sm:text-sm text-red-800">Ditolak</div>
          </div>
          <div class="bg-indigo-50 rounded-lg p-3 sm:p-4 text-center">
            <div class="text-xl sm:text-2xl font-bold text-indigo-600">{{ statistics.menjadi_order }}</div>
            <div class="text-xs sm:text-sm text-indigo-800">Menjadi Order</div>
          </div>
        </div>
      </div>

      <div class="grid lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- FORM TAMBAH RENCANA -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow p-4 sm:p-6">
            <h2 class="text-base sm:text-lg font-semibold mb-4 text-gray-800">Buat Rencana Baru</h2>

            <!-- PESAN SUKSES -->
            <div v-if="successMsg" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
              <p class="text-sm sm:text-base text-green-800">{{ successMsg }}</p>
            </div>

            <!-- PESAN ERROR -->
            <div v-if="errorMsg" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
              <p class="text-sm sm:text-base text-red-800">{{ errorMsg }}</p>
            </div>

            <form @submit.prevent="submitForm" class="space-y-4">
              <div>
                <label class="block text-sm sm:text-base font-medium text-gray-700 mb-1">
                  Produk <span class="text-red-500">*</span>
                </label>
                <select 
                  v-model="form.produk_id" 
                  class="w-full border border-gray-300 rounded-lg p-2.5 sm:p-3 text-sm sm:text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  required
                >
                  <option value="">Pilih Produk</option>
                  <option v-for="p in produkList" :key="p.id" :value="p.id">
                    {{ p.kode }} - {{ p.nama }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm sm:text-base font-medium text-gray-700 mb-1">
                  Jumlah Produksi <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.jumlah" 
                  type="number" 
                  min="1" 
                  class="w-full border border-gray-300 rounded-lg p-2.5 sm:p-3 text-sm sm:text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Masukkan jumlah"
                  required
                />
              </div>

              <div>
                <label class="block text-sm sm:text-base font-medium text-gray-700 mb-1">
                  Batas Selesai <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.batas_selesai" 
                  type="date" 
                  class="w-full border border-gray-300 rounded-lg p-2.5 sm:p-3 text-sm sm:text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  required
                />
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Target penyelesaian produksi</p>
              </div>

              <div>
                <label class="block text-sm sm:text-base font-medium text-gray-700 mb-1">
                  Catatan
                </label>
                <textarea 
                  v-model="form.catatan" 
                  rows="3"
                  class="w-full border border-gray-300 rounded-lg p-2.5 sm:p-3 text-sm sm:text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Tambahkan catatan jika diperlukan..."
                ></textarea>
              </div>

              <button 
                type="submit" 
                :disabled="loading"
                class="w-full bg-blue-600 text-white rounded-lg p-2.5 sm:p-3 text-sm sm:text-base font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <span v-if="loading">Menyimpan...</span>
                <span v-else>Simpan Rencana Produksi</span>
              </button>
            </form>
          </div>
        </div>

        <!-- DAFTAR RENCANA -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow">
            <!-- HEADER TABEL -->
            <div class="p-4 sm:p-6 border-b border-gray-200">
              <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-base sm:text-lg font-semibold text-gray-800">Daftar Rencana Produksi</h2>
                
                <div class="flex items-center gap-3 w-full sm:w-auto">
                  <!-- FILTER STATUS -->
                  <select 
                    v-model="selectedStatus" 
                    class="flex-1 sm:flex-none border border-gray-300 rounded-lg p-2 text-sm sm:text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="menunggu_persetujuan">Menunggu Persetujuan</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="menjadi_order">Menjadi Order</option>
                  </select>

                  <!-- TOMBOL RELOAD -->
                  <button 
                    @click="loadRencana" 
                    :disabled="loading"
                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                    title="Refresh data"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- TABEL DAN CARD VIEW -->
            <div class="p-4 sm:p-6">
              <div v-if="loading" class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="text-gray-500 mt-2 text-sm sm:text-base">Memuat data...</p>
              </div>

              <div v-else-if="!filteredRencana.length" class="text-center py-8">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-gray-500 text-sm sm:text-base">Tidak ada rencana produksi</p>
                <p class="text-xs sm:text-sm text-gray-400 mt-1">Buat rencana baru menggunakan form di samping</p>
              </div>

              <div v-else>
                <!-- TABLE VIEW (DESKTOP) -->
                <div class="hidden md:block overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-4 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider">Nomor Rencana</th>
                        <th class="px-4 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                        <th class="px-4 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th class="px-4 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider">Batas Selesai</th>
                        <th class="px-4 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-for="rencana in filteredRencana" :key="rencana.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-4 whitespace-nowrap">
                          <div class="font-medium text-gray-900 text-sm sm:text-base">{{ rencana.nomor_rencana }}</div>
                          <div class="text-xs text-gray-500">{{ formatDate(rencana.created_at) }}</div>
                        </td>
                        <td class="px-4 py-4">
                          <div class="font-medium text-gray-900 text-sm sm:text-base">{{ rencana.produk?.nama }}</div>
                          <div class="text-xs text-gray-500">{{ rencana.produk?.kode }}</div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                          <span class="font-medium text-gray-900 text-sm sm:text-base">{{ rencana.jumlah?.toLocaleString() }}</span>
                          <span class="text-xs text-gray-500 ml-1">{{ rencana.produk?.satuan || 'pcs' }}</span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                          <div class="text-sm sm:text-base text-gray-900">{{ formatDate(rencana.batas_selesai) }}</div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                          <span 
                            :class="['px-2 py-1 text-xs sm:text-sm font-medium rounded-full', getStatusBadge(rencana.status).class]"
                          >
                            {{ getStatusBadge(rencana.status).label }}
                          </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                          <div class="flex gap-2">
                            <button
                              v-for="button in getActionButtons(rencana)"
                              :key="button.label"
                              @click="button.action"
                              :class="['px-3 py-1 text-xs sm:text-sm rounded transition-colors', button.class]"
                            >
                              {{ button.icon }} {{ button.label }}
                            </button>
                            <span v-if="getActionButtons(rencana).length === 0" class="text-gray-400 text-xs sm:text-sm">
                              Tidak ada aksi
                            </span>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- CARD VIEW (MOBILE) -->
                <div class="md:hidden space-y-4">
                  <div 
                    v-for="rencana in filteredRencana" 
                    :key="rencana.id" 
                    class="bg-gray-50 rounded-lg p-4 shadow-sm"
                  >
                    <div class="grid grid-cols-2 gap-2 text-sm">
                      <div class="font-medium text-gray-700">Nomor Rencana</div>
                      <div>{{ rencana.nomor_rencana }} <br> <span class="text-xs text-gray-500">{{ formatDate(rencana.created_at) }}</span></div>
                      
                      <div class="font-medium text-gray-700">Produk</div>
                      <div>{{ rencana.produk?.nama }} <br> <span class="text-xs text-gray-500">{{ rencana.produk?.kode }}</span></div>
                      
                      <div class="font-medium text-gray-700">Jumlah</div>
                      <div>{{ rencana.jumlah?.toLocaleString() }} {{ rencana.produk?.satuan || 'pcs' }}</div>
                      
                      <div class="font-medium text-gray-700">Batas Selesai</div>
                      <div>{{ formatDate(rencana.batas_selesai) }}</div>
                      
                      <div class="font-medium text-gray-700">Status</div>
                      <div>
                        <span 
                          :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBadge(rencana.status).class]"
                        >
                          {{ getStatusBadge(rencana.status).label }}
                        </span>
                      </div>
                    </div>
                    <div class="mt-3 flex gap-2 flex-wrap">
                      <button
                        v-for="button in getActionButtons(rencana)"
                        :key="button.label"
                        @click="button.action"
                        :class="['px-3 py-1.5 text-xs rounded transition-colors flex-1 text-center', button.class]"
                      >
                        {{ button.icon }} {{ button.label }}
                      </button>
                      <span v-if="getActionButtons(rencana).length === 0" class="text-gray-400 text-xs">
                        Tidak ada aksi
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PPICLayout>
</template>