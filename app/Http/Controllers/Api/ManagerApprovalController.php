<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        DB::beginTransaction();
        try {
            // Validasi status rencana
            if ($plan->status !== 'menunggu_persetujuan') {
                return response()->json([
                    'success' => false,
                    'message' => 'Rencana ini sudah diproses sebelumnya. Status saat ini: ' . $plan->status
                ], 422);
            }

            $validated = $request->validate([
                'status' => ['required', Rule::in(['disetujui', 'ditolak'])],
                'catatan' => 'nullable|string|max:500',
            ]);

            if ($validated['status'] === 'disetujui') {
                // Update rencana menjadi disetujui
                $plan->update([
                    'status' => 'disetujui',
                    'disetujui_oleh' => Auth::id(),
                    'disetujui_pada' => now(),
                    'batas_selesai' => now()->addDays(7),
                    'catatan' => $validated['catatan'] ?? null,
                ]);

                // Buat order produksi otomatis
                $productionOrder = ProductionOrder::create([
                    'rencana_id' => $plan->id,
                    'produk_id' => $plan->produk_id,
                    'target_jumlah' => $plan->jumlah,
                    'status' => 'menunggu',
                    'mulai_pada' => null,
                    'selesai_pada' => null,
                    'dikerjakan_oleh' => null,
                ]);

                // Update status rencana menjadi 'menjadi_order' (opsional)
                $plan->update(['status' => 'menjadi_order']);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data' => $plan->fresh(['produk', 'pembuat', 'penyetuju']),
                    'message' => 'Rencana produksi berhasil disetujui dan order produksi telah dibuat',
                    'order_produksi' => [
                        'id' => $productionOrder->id,
                        'nomor_order' => $productionOrder->nomor_order ?? $productionOrder->id,
                        'status' => $productionOrder->status
                    ]
                ]);
            } else {
                // Jika ditolak
                $plan->update([
                    'status' => 'ditolak',
                    'disetujui_oleh' => Auth::id(),
                    'ditolak_pada' => now(),
                    'catatan' => $validated['catatan'] ?? 'Tidak ada catatan',
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data' => $plan->fresh(['produk', 'pembuat', 'penyetuju']),
                    'message' => 'Rencana produksi berhasil ditolak'
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating production plan approval: ' . $e->getMessage(), [
                'plan_id' => $plan->id,
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }
}
