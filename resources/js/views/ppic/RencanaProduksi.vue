<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import PPICLayout from '@/layouts/PPICLayout.vue'

const produkList = ref([])
const rencanaList = ref([])
const form = ref({ produk_id: '', jumlah: '', catatan: '' })
const loading = ref(false)
const errorMsg = ref('')

// 🔹 Ambil daftar produk dari master produk
const loadProduk = async () => {
  const res = await axios.get('/api/master-products')
  produkList.value = res.data
}

// 🔹 Ambil daftar rencana produksi
const loadRencana = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/production-plans')
    rencanaList.value = res.data
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

// 🔹 Simpan rencana baru
const submitForm = async () => {
  errorMsg.value = ''
  try {
    await axios.post('/api/production-plans', form.value)
    form.value = { produk_id: '', jumlah: '', catatan: '' }
    await loadRencana()
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Terjadi kesalahan.'
  }
}

// 🔹 Hapus rencana (jika masih menunggu)
const deleteRencana = async (id) => {
  if (!confirm('Hapus rencana ini?')) return
  await axios.delete(`/api/production-plans/${id}`)
  await loadRencana()
}

onMounted(() => {
  loadProduk()
  loadRencana()
})
</script>

<template>
  <PPICLayout>
    <div class="bg-white rounded-xl shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Rencana Produksi</h2>

      <!-- FORM TAMBAH -->
      <form @submit.prevent="submitForm" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div>
          <label class="block text-sm">Produk</label>
          <select v-model="form.produk_id" class="w-full border rounded p-2" required>
            <option value="">Pilih Produk</option>
            <option v-for="p in produkList" :key="p.id" :value="p.id">{{ p.nama }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm">Jumlah</label>
          <input v-model="form.jumlah" type="number" min="1" class="w-full border rounded p-2" required />
        </div>
        <div class="col-span-2">
          <label class="block text-sm">Catatan</label>
          <textarea v-model="form.catatan" class="w-full border rounded p-2"></textarea>
        </div>
        <div class="col-span-2 flex justify-end">
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Simpan Rencana
          </button>
        </div>
      </form>

      <p v-if="errorMsg" class="text-red-500 text-sm mb-3">{{ errorMsg }}</p>

      <!-- TABEL -->
      <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm">
          <thead>
            <tr class="bg-gray-100 text-left">
              <th class="p-2">Nomor</th>
              <th class="p-2">Produk</th>
              <th class="p-2">Jumlah</th>
              <th class="p-2">Status</th>
              <th class="p-2">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rencanaList" :key="r.id" class="border-t hover:bg-gray-50">
              <td class="p-2">{{ r.nomor_rencana }}</td>
              <td class="p-2">{{ r.produk?.nama }}</td>
              <td class="p-2">{{ r.jumlah }}</td>
              <td class="p-2 capitalize">{{ r.status }}</td>
              <td class="p-2">
                <button
                  v-if="r.status === 'menunggu_persetujuan'"
                  @click="deleteRencana(r.id)"
                  class="text-red-600 hover:underline"
                >
                  Hapus
                </button>
              </td>
            </tr>
            <tr v-if="!rencanaList.length">
              <td colspan="5" class="text-center text-gray-400 py-3">Belum ada rencana</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </PPICLayout>
</template>
