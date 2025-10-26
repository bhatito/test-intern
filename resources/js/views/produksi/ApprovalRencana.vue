<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import ProduksiLayout from '@/layouts/ProduksiLayout.vue'

const loading = ref(false)
const rencanaList = ref([])

const loadData = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/produksi/manager/approvals')
    rencanaList.value = res.data
  } finally {
    loading.value = false
  }
}

const approve = async (id) => {
  if (!confirm('Setujui rencana ini?')) return
  await axios.put(`/api/produksi/manager/approvals/${id}`, { status: 'disetujui' })
  await loadData()
}

const reject = async (id) => {
  const catatan = prompt('Masukkan alasan penolakan:')
  await axios.put(`/api/produksi/manager/approvals/${id}`, {
    status: 'ditolak',
    catatan,
  })
  await loadData()
}

onMounted(loadData)
</script>

<template>
  <ProduksiLayout>
    <div class="bg-white rounded-2xl shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Persetujuan Rencana Produksi</h2>

      <div v-if="loading" class="text-center py-4 text-gray-500">
        Memuat data...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm">
          <thead>
            <tr class="bg-gray-100 text-left">
              <th class="p-2">Nomor</th>
              <th class="p-2">Produk</th>
              <th class="p-2">Jumlah</th>
              <th class="p-2">Dibuat Oleh</th>
              <th class="p-2">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="r in rencanaList"
              :key="r.id"
              class="border-t hover:bg-gray-50"
            >
              <td class="p-2">{{ r.nomor_rencana }}</td>
              <td class="p-2">{{ r.produk?.nama }}</td>
              <td class="p-2">{{ r.jumlah }}</td>
              <td class="p-2">{{ r.pembuat?.name }}</td>
              <td class="p-2 flex gap-2">
                <button
                  @click="approve(r.id)"
                  class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 text-xs"
                >
                  Setujui
                </button>
                <button
                  @click="reject(r.id)"
                  class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-xs"
                >
                  Tolak
                </button>
              </td>
            </tr>
            <tr v-if="!rencanaList.length">
              <td colspan="5" class="text-center text-gray-400 py-3">
                Tidak ada rencana menunggu persetujuan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ProduksiLayout>
</template>
