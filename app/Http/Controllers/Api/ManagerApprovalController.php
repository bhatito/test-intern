<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ManagerApprovalController extends Controller
{
    /** 📋 Daftar rencana yang menunggu persetujuan */
    public function index()
    {
        $plans = ProductionPlan::with(['produk', 'pembuat'])
            ->where('status', 'menunggu_persetujuan')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($plans);
    }

    /** ✅ Approve atau ❌ Tolak */
    public function update(Request $request, ProductionPlan $plan)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['disetujui', 'ditolak'])],
            'catatan' => 'nullable|string',
        ]);

        if ($validated['status'] === 'disetujui') {
            // Update rencana
            $plan->update([
                'status' => 'disetujui',
                'disetujui_oleh' => Auth::id(),
                'disetujui_pada' => now(),
                'batas_selesai' => now()->addDays(7),
                'catatan' => $validated['catatan'] ?? null,
            ]);

            // Buat order produksi otomatis
            ProductionOrder::create([
                'rencana_id' => $plan->id,
                'produk_id' => $plan->produk_id,
                'jumlah_order' => $plan->jumlah,
                'target_selesai' => now()->addDays(7),
                'status' => 'menunggu',
            ]);

            $plan->update(['status' => 'menjadi_order']);
        } else {
            // Jika ditolak
            $plan->update([
                'status' => 'ditolak',
                'disetujui_oleh' => Auth::id(),
                'ditolak_pada' => now(),
                'catatan' => $validated['catatan'] ?? null,
            ]);
        }

        return response()->json($plan->fresh(['produk', 'pembuat']));
    }
}
