<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductionPlanController extends Controller
{
    /** 📋 List semua rencana produksi */
    public function index()
    {
        // Ambil semua rencana dengan relasi produk & pembuat
        $plans = ProductionPlan::with(['produk', 'pembuat', 'penyetuju'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($plans);
    }

    /** ➕ Tambah rencana baru oleh Staff PPIC */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'produk_id' => 'required|exists:master_products,id',
            'jumlah' => 'required|integer|min:1',
            'catatan' => 'nullable|string',
        ]);

        $plan = ProductionPlan::create([
            'produk_id' => $validated['produk_id'],
            'jumlah' => $validated['jumlah'],
            'catatan' => $validated['catatan'] ?? null,
            'dibuat_oleh' => Auth::id(),
            'status' => 'menunggu_persetujuan',
        ]);

        return response()->json($plan, 201);
    }

    /** ❌ Hapus rencana (jika masih menunggu) */
    public function destroy(ProductionPlan $plan)
    {
        if ($plan->status !== 'menunggu_persetujuan') {
            return response()->json(['message' => 'Rencana tidak bisa dihapus karena sudah diproses.'], 403);
        }

        $plan->delete();

        return response()->json(['message' => 'Rencana berhasil dihapus.']);
    }
}
