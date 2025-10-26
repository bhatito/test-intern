<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MasterProductController extends Controller
{

    public function index()
    {
        $produk = MasterProduct::orderBy('nama')->get();
        return response()->json($produk);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:master_products,kode',
            'nama' => 'required|string|max:255',
            'satuan' => 'required|string|max:50',
            'deskripsi' => 'nullable|string',
        ]);

        $produk = MasterProduct::create($validated);

        return response()->json([
            'message' => 'Produk berhasil ditambahkan',
            'data' => $produk
        ], 201);
    }

    public function show(MasterProduct $master_product)
    {
        return response()->json($master_product);
    }

    public function update(Request $request, MasterProduct $master_product)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:master_products,kode,' . $master_product->id,
            'nama' => 'required|string|max:255',
            'satuan' => 'required|string|max:50',
            'deskripsi' => 'nullable|string',
        ]);

        $master_product->update($validated);

        return response()->json([
            'message' => 'Produk berhasil diperbarui',
            'data' => $master_product
        ]);
    }

    public function destroy(MasterProduct $master_product)
    {
        $master_product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus']);
    }
}
