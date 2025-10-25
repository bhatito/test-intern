<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ProductionPlanController extends Controller
{
    /** 📋 List semua rencana produksi dengan filter */
    public function index(Request $request)
    {
        $query = ProductionPlan::with(['produk', 'pembuat', 'penyetuju'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by periode
        if ($request->has('periode_awal') && $request->has('periode_akhir')) {
            $query->whereBetween('created_at', [
                $request->periode_awal,
                $request->periode_akhir
            ]);
        }

        $plans = $query->get();

        return response()->json([
            'data' => $plans,
            'message' => 'Data rencana produksi berhasil diambil'
        ]);
    }

    /** 👁️ Detail rencana produksi */
    public function show(ProductionPlan $plan)
    {
        $plan->load(['produk', 'pembuat', 'penyetuju', 'orderProduksi']);

        return response()->json([
            'data' => $plan,
            'message' => 'Detail rencana produksi berhasil diambil'
        ]);
    }

    /** ➕ Tambah rencana baru oleh Staff PPIC */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'produk_id' => 'required|exists:master_products,id',
            'jumlah' => 'required|integer|min:1',
            'batas_selesai' => 'required|date|after:today',
            'catatan' => 'nullable|string|max:500',
        ]);

        // Cek apakah produk exists
        $produk = MasterProduct::find($validated['produk_id']);
        if (!$produk) {
            return response()->json([
                'message' => 'Produk tidak ditemukan'
            ], 404);
        }

        $plan = ProductionPlan::create([
            'produk_id' => $validated['produk_id'],
            'jumlah' => $validated['jumlah'],
            'batas_selesai' => $validated['batas_selesai'],
            'catatan' => $validated['catatan'] ?? null,
            'dibuat_oleh' => Auth::id(),
            'status' => 'menunggu_persetujuan',
        ]);

        // Load relasi untuk response
        $plan->load(['produk', 'pembuat']);

        return response()->json([
            'data' => $plan,
            'message' => 'Rencana produksi berhasil dibuat dan menunggu persetujuan Manager Produksi'
        ], 201);
    }

    /** ✏️ Update rencana (hanya jika masih draft/menunggu) */
    public function update(Request $request, ProductionPlan $plan)
    {
        // Hanya bisa update jika status masih draft atau menunggu persetujuan
        if (!in_array($plan->status, ['draft', 'menunggu_persetujuan'])) {
            return response()->json([
                'message' => 'Rencana tidak bisa diubah karena sudah diproses lebih lanjut'
            ], 403);
        }

        $validated = $request->validate([
            'produk_id' => 'sometimes|exists:master_products,id',
            'jumlah' => 'sometimes|integer|min:1',
            'batas_selesai' => 'sometimes|date|after:today',
            'catatan' => 'nullable|string|max:500',
        ]);

        $plan->update($validated);
        $plan->load(['produk', 'pembuat']);

        return response()->json([
            'data' => $plan,
            'message' => 'Rencana produksi berhasil diperbarui'
        ]);
    }

    /** ❌ Hapus rencana (hanya jika masih bisa dihapus) */
    public function destroy(ProductionPlan $plan)
    {
        // Hanya bisa hapus jika status masih draft atau menunggu persetujuan
        if (!in_array($plan->status, ['draft', 'menunggu_persetujuan'])) {
            return response()->json([
                'message' => 'Rencana tidak bisa dihapus karena sudah diproses lebih lanjut'
            ], 403);
        }

        $plan->delete();

        return response()->json([
            'message' => 'Rencana produksi berhasil dihapus'
        ]);
    }

    /** 📊 Statistik rencana produksi */
    public function statistics()
    {
        $total = ProductionPlan::count();
        $menunggu = ProductionPlan::where('status', 'menunggu_persetujuan')->count();
        $disetujui = ProductionPlan::where('status', 'disetujui')->count();
        $ditolak = ProductionPlan::where('status', 'ditolak')->count();
        $menjadiOrder = ProductionPlan::where('status', 'menjadi_order')->count();

        return response()->json([
            'data' => [
                'total' => $total,
                'menunggu_persetujuan' => $menunggu,
                'disetujui' => $disetujui,
                'ditolak' => $ditolak,
                'menjadi_order' => $menjadiOrder,
            ],
            'message' => 'Statistik rencana produksi berhasil diambil'
        ]);
    }
}
