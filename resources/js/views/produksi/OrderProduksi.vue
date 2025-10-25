<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import ProduksiLayout from '@/layouts/ProduksiLayout.vue'

const loading = ref(false)
const orders = ref([])
const filter = ref('') // '', 'menunggu', 'dikerjakan', 'selesai'

const loadOrders = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/production-orders', {
      params: { status: filter.value || undefined }
    })
    orders.value = res.data
  } finally {
    loading.value = false
  }
}

const startOrder = async (id) => {
  if (!confirm('Mulai produksi untuk order ini?')) return
  await axios.put(`/api/production-orders/${id}/start`)
  await loadOrders()
}

const completeOrder = async (order) => {
  const jumlah_aktual = parseInt(prompt('Masukkan jumlah aktual:', order.target_jumlah ?? 0) || '0', 10)
  const jumlah_reject = parseInt(prompt('Masukkan jumlah reject (NG):', '0') || '0', 10)
  const catatan = prompt('Catatan (opsional):') || undefined

  await axios.put(`/api/production-orders/${order.id}/complete`, {
    jumlah_aktual, jumlah_reject, catatan
  })
  await loadOrders()
}

onMounted(loadOrders)
</script>

<template>
  <ProduksiLayout>
    <div class="bg-white rounded-2xl shadow p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <h2 class="text-xl font-semibold">Order Produksi</h2>
        <div class="flex items-center gap-2">
          <select v-model="filter" @change="loadOrders" class="border rounded p-2 text-sm">
            <option value="">Semua Status</option>
            <option value="menunggu">Menunggu</option>
            <option value="dikerjakan">Dikerjakan</option>
            <option value="selesai">Selesai</option>
          </select>
          <button @click="loadOrders" class="px-3 py-2 text-sm rounded bg-gray-100 hover:bg-gray-200">
            Refresh
          </button>
        </div>
      </div>

      <div v-if="loading" class="text-gray-500">Memuat data…</div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm">
          <thead>
            <tr class="bg-gray-100 text-left">
              <th class="p-2">Nomor</th>
              <th class="p-2">Produk</th>
              <th class="p-2">Target</th>
              <th class="p-2">Status</th>
              <th class="p-2">Mulai</th>
              <th class="p-2">Selesai</th>
              <th class="p-2">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="o in orders" :key="o.id" class="border-t hover:bg-gray-50">
              <td class="p-2">{{ o.nomor_order }}</td>
              <td class="p-2">{{ o.produk?.nama }}</td>
              <td class="p-2">{{ o.target_jumlah ?? o.jumlah_order ?? '-' }}</td>
              <td class="p-2 capitalize">{{ o.status }}</td>
              <td class="p-2">{{ o.mulai_pada ?? '-' }}</td>
              <td class="p-2">{{ o.selesai_pada ?? '-' }}</td>
              <td class="p-2 flex gap-2">
                <button
                  v-if="o.status === 'menunggu'"
                  @click="startOrder(o.id)"
                  class="px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700"
                >
                  Mulai
                </button>
                <button
                  v-if="o.status === 'dikerjakan'"
                  @click="completeOrder(o)"
                  class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700"
                >
                  Selesai
                </button>
              </td>
            </tr>
            <tr v-if="!orders.length">
              <td colspan="7" class="text-center text-gray-400 py-4">Tidak ada order.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ProduksiLayout>
</template>
