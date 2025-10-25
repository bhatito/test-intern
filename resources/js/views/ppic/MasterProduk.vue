<script setup>
import PPICLayout from '@/layouts/PPICLayout.vue'
import { ref, onMounted } from 'vue'
import axios from 'axios'

const produkList = ref([])
const form = ref({ id: null, kode: '', nama: '', satuan: '', deskripsi: '' })
const loading = ref(false)
const editMode = ref(false)
const errorMsg = ref('')

const loadData = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/ppic/master-products')
    produkList.value = res.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const submitForm = async () => {
  errorMsg.value = ''
  try {
    if (editMode.value) {
      await axios.put(`/api/master-products/${form.value.id}`, form.value)
    } else {
      await axios.post('/api/master-products', form.value)
    }
    await loadData()
    resetForm()
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Terjadi kesalahan.'
  }
}

const editProduk = (item) => {
  form.value = { ...item }
  editMode.value = true
}

const deleteProduk = async (id) => {
  if (!confirm('Hapus produk ini?')) return
  await axios.delete(`/api/master-products/${id}`)
  await loadData()
}

const resetForm = () => {
  form.value = { id: null, kode: '', nama: '', satuan: '', deskripsi: '' }
  editMode.value = false
}

onMounted(loadData)
</script>

<template>
  <PPICLayout>
    <div class="bg-white rounded-xl shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Master Produk</h2>

      <!-- Form tambah / edit -->
      <form @submit.prevent="submitForm" class="grid md:grid-cols-2 gap-4 mb-6">
        <div>
          <label class="block text-sm">Kode Produk</label>
          <input v-model="form.kode" type="text" class="w-full border rounded p-2" required />
        </div>
        <div>
          <label class="block text-sm">Nama Produk</label>
          <input v-model="form.nama" type="text" class="w-full border rounded p-2" required />
        </div>
        <div>
          <label class="block text-sm">Satuan</label>
          <input v-model="form.satuan" type="text" class="w-full border rounded p-2" required />
        </div>
        <div>
          <label class="block text-sm">Deskripsi</label>
          <textarea v-model="form.deskripsi" class="w-full border rounded p-2"></textarea>
        </div>
        <div class="col-span-2 flex justify-end gap-2 mt-2">
          <button type="button" @click="resetForm" class="px-4 py-2 bg-gray-300 rounded">Reset</button>
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            {{ editMode ? 'Update' : 'Simpan' }}
          </button>
        </div>
      </form>

      <p v-if="errorMsg" class="text-red-500 text-sm mb-3">{{ errorMsg }}</p>

      <!-- Tabel data -->
      <div class="overflow-x-auto">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                <tr class="bg-gray-100 text-left">
                    <th class="p-2">Kode</th>
                    <th class="p-2">Nama</th>
                    <th class="p-2">Satuan</th>
                    <th class="p-2">Deskripsi</th>
                    <th class="p-2">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in produkList" :key="item.id" class="border-t hover:bg-gray-50">
                    <td class="p-2">{{ item.kode }}</td>
                    <td class="p-2">{{ item.nama }}</td>
                    <td class="p-2">{{ item.satuan }}</td>
                    <td class="p-2">{{ item.deskripsi || '-' }}</td>
                    <td class="p-2">
                    <button @click="editProduk(item)" class="text-blue-600 mr-2">Edit</button>
                    <button @click="deleteProduk(item.id)" class="text-red-600">Hapus</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>
  </PPICLayout>
</template>
