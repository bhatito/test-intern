<script setup>
import { ref, onMounted } from 'vue';
import ProduksiLayout from '@/layouts/ProduksiLayout.vue';
import axios from 'axios';

axios.defaults.withCredentials = true;

const loading = ref(false);
const generating = ref(false);
const laporan = ref([]);
const realtimeStats = ref({});
const errorMessage = ref('');
const successMessage = ref('');
const showGenerateModal = ref(false);
const selectedLaporan = ref(null);
const showDetailModal = ref(false);
const laporanDetail = ref(null);

const filterForm = ref({
    periode_awal: new Date().toISOString().split('T')[0],
    periode_akhir: new Date().toISOString().split('T')[0]
});

const generateForm = ref({
    periode_awal: new Date().toISOString().split('T')[0],
    periode_akhir: new Date().toISOString().split('T')[0],
    catatan: ''
});

const loadLaporan = async () => {
    try {
        loading.value = true;
        errorMessage.value = '';

        const params = new URLSearchParams();
        if (filterForm.value.periode_awal) {
            params.append('periode_awal', filterForm.value.periode_awal);
        }
        if (filterForm.value.periode_akhir) {
            params.append('periode_akhir', filterForm.value.periode_akhir);
        }

        const response = await axios.get(`/api/produksi/laporan?${params}`);
        
        if (response.data.success) {
            laporan.value = response.data.data;
        } else {
            errorMessage.value = response.data.message || 'Gagal memuat data laporan';
            alert(errorMessage.value);
        }
    } catch (error) {
        console.error('Error loading reports:', error);
        errorMessage.value = error.response?.data?.message || 'Gagal memuat data laporan';
        alert(errorMessage.value);
    } finally {
        loading.value = false;
    }
};

const loadRealtimeStats = async () => {
    try {
        const response = await axios.get('/api/produksi/laporan/stats/realtime');
        if (response.data.success) {
            realtimeStats.value = response.data.data;
        }
    } catch (error) {
        console.error('Error loading realtime stats:', error);
    }
};

const generateLaporan = async () => {
    try {
        generating.value = true;
        errorMessage.value = '';
        successMessage.value = '';

        const response = await axios.post('/api/produksi/laporan/generate', generateForm.value);
        
        if (response.data.success) {
            successMessage.value = 'Laporan berhasil digenerate!';
            generateForm.value.catatan = '';
            showGenerateModal.value = false;
            await loadLaporan();

            laporanDetail.value = response.data.data;
            showDetailModal.value = true;
        } else {
            errorMessage.value = response.data.message || 'Gagal generate laporan';
            alert(errorMessage.value);
        }
    } catch (error) {
        console.error('Error generating report:', error);
        errorMessage.value = error.response?.data?.message || 'Gagal generate laporan';
        alert(errorMessage.value);
    } finally {
        generating.value = false;
    }
};

const exportLaporan = async (laporanId) => {
    try {
        loading.value = true;
        errorMessage.value = '';

        const res = await axios.get(`/api/produksi/laporan/${laporanId}/export-excel`, {
            responseType: 'blob'
        });

        const contentType = res.headers['content-type'];
        if (!contentType.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
            const errorText = await res.data.text();
            try {
                const errorData = JSON.parse(errorText);
                throw new Error(errorData.message || 'Received non-Excel response');
            } catch (e) {
                throw new Error('Received non-Excel response or corrupted file');
            }
        }

        const blob = new Blob([res.data], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');

        let filename = `Laporan_Produksi_${laporanId}_${new Date().getTime()}.xlsx`;
        const contentDisposition = res.headers['content-disposition'];
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/i);
            if (filenameMatch && filenameMatch[1]) {
                filename = filenameMatch[1].replace(/[^A-Za-z0-9_-]/g, '_');
            }
        }

        link.href = url;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        setTimeout(() => {
            window.URL.revokeObjectURL(url);
        }, 100);
    } catch (error) {
        console.error('Gagal export laporan:', error);
        errorMessage.value = error.message || 'Gagal export laporan Excel';
        alert(errorMessage.value);
    } finally {
        loading.value = false;
    }
};

const viewLaporanDetail = async (laporanItem) => {
    try {
        selectedLaporan.value = laporanItem;
        const response = await axios.get(`/api/produksi/laporan/${laporanItem.id}/preview`);
        
        if (response.data.success) {
            laporanDetail.value = response.data.data;
            showDetailModal.value = true;
        } else {
            errorMessage.value = response.data.message || 'Gagal memuat detail laporan';
            alert(errorMessage.value);
        }
    } catch (error) {
        console.error('Error loading report detail:', error);
        errorMessage.value = error.response?.data?.message || 'Gagal memuat detail laporan';
        alert(errorMessage.value);
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const formatDateTime = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

const formatPercent = (num) => {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num || 0) + '%';
};

const resetGenerateForm = () => {
    generateForm.value = {
        periode_awal: new Date().toISOString().split('T')[0],
        periode_akhir: new Date().toISOString().split('T')[0],
        catatan: ''
    };
};

const calculatePeriodDays = (start, end) => {
    const startDate = new Date(start);
    const endDate = new Date(end);
    const diffTime = Math.abs(endDate - startDate);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
};

onMounted(async () => {
    await axios.get('/sanctum/csrf-cookie');
    loadLaporan();
    loadRealtimeStats();
});
</script>

<template>
    <ProduksiLayout>
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Laporan Produksi</h1>
                        <p class="text-gray-600 mt-1">Kelola dan pantau laporan kinerja produksi</p>
                    </div>
                    <button
                        @click="showGenerateModal = true"
                        class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Generate Laporan
                    </button>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-600 text-sm font-medium">Order Hari Ini</p>
                                <p class="text-2xl font-bold text-blue-800 mt-1">
                                    {{ formatNumber(realtimeStats.harian?.order_dibuat) }}
                                </p>
                            </div>
                            <div class="text-blue-600 text-2xl">📦</div>
                        </div>
                    </div>

                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-600 text-sm font-medium">Selesai Hari Ini</p>
                                <p class="text-2xl font-bold text-green-800 mt-1">
                                    {{ formatNumber(realtimeStats.harian?.order_selesai) }}
                                </p>
                            </div>
                            <div class="text-green-600 text-2xl">✅</div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-yellow-600 text-sm font-medium">Produksi Hari Ini</p>
                                <p class="text-2xl font-bold text-yellow-800 mt-1">
                                    {{ formatNumber(realtimeStats.harian?.produksi_harian) }}
                                </p>
                                <p class="text-xs text-yellow-600 mt-1">pcs</p>
                            </div>
                            <div class="text-yellow-600 text-2xl">⚙️</div>
                        </div>
                    </div>

                    <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-600 text-sm font-medium">Total Laporan</p>
                                <p class="text-2xl font-bold text-purple-800 mt-1">
                                    {{ formatNumber(laporan.length) }}
                                </p>
                            </div>
                            <div class="text-purple-600 text-2xl">📊</div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Laporan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Periode Awal</label>
                            <input
                                type="date"
                                v-model="filterForm.periode_awal"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Periode Akhir</label>
                            <input
                                type="date"
                                v-model="filterForm.periode_akhir"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            />
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="loadLaporan"
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                            >
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <span class="text-red-600 text-lg">⚠️</span>
                    <div>
                        <p class="text-red-800 font-medium">Terjadi Kesalahan</p>
                        <p class="text-red-600 text-sm">{{ errorMessage }}</p>
                    </div>
                    <button
                        @click="errorMessage = ''"
                        class="ml-auto px-3 py-1 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>

            <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <span class="text-green-600 text-lg">✅</span>
                    <div>
                        <p class="text-green-800 font-medium">Berhasil</p>
                        <p class="text-green-600 text-sm">{{ successMessage }}</p>
                    </div>
                    <button
                        @click="successMessage = ''"
                        class="ml-auto px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">Daftar Laporan Produksi</h3>
                    <div class="text-sm text-gray-500">
                        Menampilkan {{ laporan.length }} laporan
                    </div>
                </div>

                <div v-if="loading" class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
                    <p class="text-gray-500 mt-2">Memuat data laporan...</p>
                </div>

                <div v-else-if="laporan.length === 0" class="text-center py-8">
                    <div class="text-gray-400 text-4xl mb-3">📊</div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">Belum ada laporan produksi</h4>
                    <p class="text-gray-500">Generate laporan pertama Anda untuk melihat data produksi</p>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="laporanItem in laporan"
                        :key="laporanItem.id"
                        class="border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200"
                    >
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="font-semibold text-gray-900">{{ laporanItem.nomor_laporan }}</span>
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                                            {{ calculatePeriodDays(laporanItem.periode_awal, laporanItem.periode_akhir) }} hari
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">Periode:</span>
                                            <p class="font-medium text-gray-900 mt-1">
                                                {{ formatDate(laporanItem.periode_awal) }} - {{ formatDate(laporanItem.periode_akhir) }}
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <span class="text-gray-500">Dibuat Oleh:</span>
                                            <p class="font-medium text-gray-900 mt-1">
                                                {{ laporanItem.pembuat?.name || 'Tidak Diketahui' }}
                                            </p>
                                        </div>

                                        <div>
                                            <span class="text-gray-500">Tanggal Dibuat:</span>
                                            <p class="font-medium text-gray-900 mt-1">
                                                {{ formatDateTime(laporanItem.created_at) }}
                                            </p>
                                        </div>

                                        <div>
                                            <span class="text-gray-500">Catatan:</span>
                                            <p class="font-medium text-gray-900 mt-1">
                                                {{ laporanItem.catatan || 'Tidak ada catatan' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-2 mt-3">
                                <button
                                    @click="viewLaporanDetail(laporanItem)"
                                    class="flex items-center gap-2 px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Lihat Detail
                                </button>
                                
                                <button
                                    @click="exportLaporan(laporanItem.id)"
                                    class="flex items-center gap-2 px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Export Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showGenerateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Generate Laporan Baru</h3>
                        <button
                            @click="showGenerateModal = false; resetGenerateForm()"
                            class="text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="generateLaporan" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Periode Awal</label>
                            <input
                                type="date"
                                v-model="generateForm.periode_awal"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Periode Akhir</label>
                            <input
                                type="date"
                                v-model="generateForm.periode_akhir"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                            <textarea
                                v-model="generateForm.catatan"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                placeholder="Tambahkan catatan untuk laporan ini..."
                            ></textarea>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button
                                type="button"
                                @click="showGenerateModal = false; resetGenerateForm()"
                                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="generating"
                                class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors flex items-center justify-center gap-2"
                            >
                                <svg v-if="generating" class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ generating ? 'Generating...' : 'Generate' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div v-if="showDetailModal && laporanDetail" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Detail Laporan Produksi</h3>
                        <button
                            @click="showDetailModal = false"
                            class="text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-800 mb-3">Informasi Laporan</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Nomor Laporan:</span>
                                    <span class="font-medium">{{ laporanDetail.laporan?.nomor_laporan }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Periode:</span>
                                    <span class="font-medium">{{ laporanDetail.statistik?.periode?.awal }} - {{ laporanDetail.statistik?.periode?.akhir }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Dibuat Oleh:</span>
                                    <span class="font-medium">{{ laporanDetail.laporan?.pembuat?.name || 'Tidak Diketahui' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tanggal Dibuat:</span>
                                    <span class="font-medium">{{ formatDateTime(laporanDetail.laporan?.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 rounded-lg p-4">
                            <h4 class="font-medium text-green-800 mb-3">Ringkasan Produksi</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-green-600">Total Order:</span>
                                    <span class="font-medium">{{ formatNumber(laporanDetail.statistik?.ringkasan?.total_order) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-green-600">Total Produksi:</span>
                                    <span class="font-medium">{{ formatNumber(laporanDetail.statistik?.ringkasan?.total_produksi) }} pcs</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-green-600">Efisiensi:</span>
                                    <span class="font-medium">{{ formatPercent(laporanDetail.statistik?.ringkasan?.efisiensi) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-green-600">Tingkat Reject:</span>
                                    <span class="font-medium">{{ formatPercent(laporanDetail.statistik?.ringkasan?.tingkat_reject) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg p-4 mb-6">
                        <h4 class="font-medium text-gray-800 mb-3">Statistik Per Produk</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3">Kode</th>
                                        <th scope="col" class="px-4 py-3">Produk</th>
                                        <th scope="col" class="px-4 py-3">Order</th>
                                        <th scope="col" class="px-4 py-3">Target</th>
                                        <th scope="col" class="px-4 py-3">Produksi</th>
                                        <th scope="col" class="px-4 py-3">Reject</th>
                                        <th scope="col" class="px-4 py-3">Efisiensi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!laporanDetail.statistik?.statistik_produk?.length" class="border-b">
                                        <td colspan="7" class="px-4 py-3 text-center">Tidak ada data produk</td>
                                    </tr>
                                    <tr
                                        v-for="produk in laporanDetail.statistik?.statistik_produk"
                                        :key="produk.kode"
                                        class="border-b hover:bg-gray-50"
                                    >
                                        <td class="px-4 py-3">{{ produk.kode }}</td>
                                        <td class="px-4 py-3">{{ produk.produk }}</td>
                                        <td class="px-4 py-3">{{ formatNumber(produk.total_order) }}</td>
                                        <td class="px-4 py-3">{{ formatNumber(produk.total_target) }}</td>
                                        <td class="px-4 py-3">{{ formatNumber(produk.total_produksi) }}</td>
                                        <td class="px-4 py-3">{{ formatNumber(produk.total_reject) }}</td>
                                        <td class="px-4 py-3">{{ formatPercent(produk.efisiensi) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="laporanDetail.laporan?.catatan" class="bg-blue-50 rounded-lg p-4 mb-6">
                        <h4 class="font-medium text-blue-800 mb-2">Catatan</h4>
                        <p class="text-blue-700 text-sm">{{ laporanDetail.laporan.catatan }}</p>
                    </div>

                    <div class="flex gap-3">
                        <button
                            @click="exportLaporan(laporanDetail.laporan?.id)"
                            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export ke Excel
                        </button>
                        <button
                            @click="showDetailModal = false"
                            class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </ProduksiLayout>
</template>