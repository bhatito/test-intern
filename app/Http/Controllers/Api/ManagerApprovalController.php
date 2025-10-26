<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderHistory;
use App\Services\ProductionPlanHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ManagerApprovalController extends Controller
{
    public function index()
    {
        $plans = ProductionPlan::with(['produk', 'pembuat'])
            ->where('status', 'menunggu_persetujuan')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($plans);
    }

    public function update(Request $request, ProductionPlan $plan)
    {
        DB::beginTransaction();
        try {

            $statusSebelum = $plan->status;

            if ($statusSebelum !== 'menunggu_persetujuan') {
                return response()->json([
                    'success' => false,
                    'message' => 'Rencana ini sudah diproses sebelumnya. Status saat ini: ' . $statusSebelum
                ], 422);
            }

            $validated = $request->validate([
                'status' => ['required', Rule::in(['disetujui', 'ditolak'])],
                'catatan' => 'nullable|string|max:500',
            ]);

            if ($validated['status'] === 'disetujui') {

                $plan->update([
                    'status' => 'disetujui',
                    'disetujui_oleh' => Auth::id(),
                    'disetujui_pada' => now(),
                    'batas_selesai' => now()->addDays(7),
                    'catatan' => $validated['catatan'] ?? null,
                ]);

                ProductionPlanHistoryService::catatPersetujuan(
                    $plan,
                    $statusSebelum,
                    $validated['catatan'] ?? null
                );

                $productionOrder = ProductionOrder::create([
                    'rencana_id' => $plan->id,
                    'produk_id' => $plan->produk_id,
                    'target_jumlah' => $plan->jumlah,
                    'status' => 'menunggu',
                    'mulai_pada' => null,
                    'selesai_pada' => null,
                    'dikerjakan_oleh' => null,
                ]);

                if (!$productionOrder->nomor_order) {
                    $latestOrder = ProductionOrder::latest()->first();
                    $no = $latestOrder ? intval(substr($latestOrder->nomor_order, -4)) + 1 : 1;
                    $productionOrder->update([
                        'nomor_order' => 'ORD-' . str_pad($no, 4, '0', STR_PAD_LEFT)
                    ]);
                }

                $statusSebelumOrder = $plan->status;
                $plan->update(['status' => 'menjadi_order']);

                ProductionPlanHistoryService::catatMenjadiOrder(
                    $plan,
                    $statusSebelumOrder
                );

                ProductionOrderHistory::create([
                    'order_id' => $productionOrder->id,
                    'status_sebelumnya' => null,
                    'status_baru' => 'menunggu',
                    'diubah_oleh' => Auth::id(),
                    'keterangan' => 'Order produksi dibuat dari rencana: ' . $plan->nomor_rencana,
                    'diubah_pada' => now()
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data' => $plan->fresh(['produk', 'pembuat', 'penyetuju']),
                    'message' => 'Rencana produksi berhasil disetujui dan order produksi telah dibuat',
                    'order_produksi' => [
                        'id' => $productionOrder->id,
                        'nomor_order' => $productionOrder->nomor_order,
                        'status' => $productionOrder->status
                    ],
                    'history_logs' => [
                        'persetujuan_rencana',
                        'menjadi_order_produksi',
                        'pembuatan_order_baru'
                    ]
                ]);
            } else {

                $plan->update([
                    'status' => 'ditolak',
                    'disetujui_oleh' => Auth::id(),
                    'ditolak_pada' => now(),
                    'catatan' => $validated['catatan'] ?? 'Tidak ada catatan',
                ]);

                ProductionPlanHistoryService::catatPenolakan(
                    $plan,
                    $statusSebelum,
                    $validated['catatan'] ?? 'Tidak ada alasan'
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data' => $plan->fresh(['produk', 'pembuat', 'penyetuju']),
                    'message' => 'Rencana produksi berhasil ditolak',
                    'history_logs' => [
                        'penolakan_rencana'
                    ]
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating production plan approval: ' . $e->getMessage(), [
                'plan_id' => $plan->id,
                'user_id' => Auth::id(),
                'status_sebelum' => $statusSebelum ?? 'unknown',
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stats()
    {
        try {
            $stats = [
                'menunggu_persetujuan' => ProductionPlan::where('status', 'menunggu_persetujuan')->count(),
                'total_diselesaikan_hari_ini' => ProductionPlan::whereDate('disetujui_pada', now()->today())
                    ->where('status', 'disetujui')
                    ->count(),
                'total_ditolak_bulan_ini' => ProductionPlan::whereMonth('ditolak_pada', now()->month)
                    ->whereYear('ditolak_pada', now()->year)
                    ->where('status', 'ditolak')
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistik persetujuan berhasil diambil'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching approval stats: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik: ' . $e->getMessage()
            ], 500);
        }
    }
}
