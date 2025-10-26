<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
use App\Models\MasterProduct;
use App\Models\ProductionPlanHistory;
use App\Services\ProductionPlanHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ProductionPlanController extends Controller
{

    public function index(Request $request)
    {
        try {
            $query = ProductionPlan::with(['produk', 'pembuat', 'penyetuju'])
                ->orderBy('created_at', 'desc');

            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('periode_awal') && $request->has('periode_akhir')) {
                $query->whereBetween('created_at', [
                    $request->periode_awal,
                    $request->periode_akhir
                ]);
            }

            if ($request->has('produk_id') && $request->produk_id) {
                $query->where('produk_id', $request->produk_id);
            }

            $plans = $query->get();

            return response()->json([
                'success' => true,
                'data' => $plans,
                'message' => 'Data rencana produksi berhasil diambil',
                'count' => $plans->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data rencana: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function show(ProductionPlan $plan)
    {
        try {
            $plan->load(['produk', 'pembuat', 'penyetuju', 'orderProduksi', 'histories.user']);

            return response()->json([
                'success' => true,
                'data' => $plan,
                'message' => 'Detail rencana produksi berhasil diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail rencana: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'produk_id' => 'required|exists:master_products,id',
                'jumlah' => 'required|integer|min:1',
                'batas_selesai' => 'required|date|after:today',
                'catatan' => 'nullable|string|max:500',
            ]);

            $produk = MasterProduct::find($validated['produk_id']);
            if (!$produk) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan'
                ], 404);
            }

            $nomorRencana = 'RP-' . date('Ymd') . '-' . str_pad(ProductionPlan::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            $plan = ProductionPlan::create([
                'nomor_rencana' => $nomorRencana,
                'produk_id' => $validated['produk_id'],
                'jumlah' => $validated['jumlah'],
                'batas_selesai' => $validated['batas_selesai'],
                'catatan' => $validated['catatan'] ?? null,
                'dibuat_oleh' => Auth::id(),
                'status' => 'draft',
                'diajukan_pada' => now(),
            ]);

            ProductionPlanHistoryService::catatPembuatan($plan);

            DB::commit();

            $plan->load(['produk', 'pembuat']);

            return response()->json([
                'success' => true,
                'data' => $plan,
                'message' => 'Rencana produksi berhasil dibuat dan menunggu persetujuan Manager Produksi'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat rencana produksi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, ProductionPlan $plan)
    {
        DB::beginTransaction();
        try {
            if (!in_array($plan->status, ['draft', 'menunggu_persetujuan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rencana tidak bisa diubah karena sudah diproses lebih lanjut. Status saat ini: ' . $plan->status
                ], 403);
            }

            $validated = $request->validate([
                'produk_id' => 'sometimes|exists:master_products,id',
                'jumlah' => 'sometimes|integer|min:1',
                'batas_selesai' => 'sometimes|date|after:today',
                'catatan' => 'nullable|string|max:500',
            ]);

            $statusSebelum = $plan->status;

            $plan->update($validated);

            if ($plan->wasChanged()) {
                ProductionPlanHistory::create([
                    'rencana_id' => $plan->id,
                    'user_id' => Auth::id(),
                    'aksi' => 'diupdate',
                    'status_sebelum' => $statusSebelum,
                    'status_baru' => $plan->status,
                    'keterangan' => 'Data rencana diperbarui oleh PPIC',
                    'waktu_aksi' => now(),
                ]);
            }

            DB::commit();

            $plan->load(['produk', 'pembuat']);

            return response()->json([
                'success' => true,
                'data' => $plan,
                'message' => 'Rencana produksi berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui rencana: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(ProductionPlan $plan)
    {
        DB::beginTransaction();
        try {
            if (!in_array($plan->status, ['draft', 'menunggu_persetujuan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rencana tidak bisa dihapus karena sudah diproses lebih lanjut. Status saat ini: ' . $plan->status
                ], 403);
            }

            ProductionPlanHistory::create([
                'rencana_id' => $plan->id,
                'user_id' => Auth::id(),
                'aksi' => 'dihapus',
                'status_sebelum' => $plan->status,
                'status_baru' => 'dihapus',
                'keterangan' => 'Rencana produksi dihapus oleh PPIC',
                'waktu_aksi' => now(),
            ]);

            $plan->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rencana produksi berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus rencana: ' . $e->getMessage()
            ], 500);
        }
    }

    public function statistics()
    {
        try {
            $stats = [
                'total' => ProductionPlan::count(),
                'menunggu_persetujuan' => ProductionPlan::where('status', 'menunggu_persetujuan')->count(),
                'disetujui' => ProductionPlan::where('status', 'disetujui')->count(),
                'ditolak' => ProductionPlan::where('status', 'ditolak')->count(),
                'menjadi_order' => ProductionPlan::where('status', 'menjadi_order')->count(),
                'draft' => ProductionPlan::where('status', 'draft')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistik rencana produksi berhasil diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $search = $request->query('search');
            $status = $request->query('status');

            $query = ProductionPlan::with(['produk', 'pembuat'])
                ->orderBy('created_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nomor_rencana', 'like', "%{$search}%")
                        ->orWhereHas('produk', function ($q) use ($search) {
                            $q->where('nama', 'like', "%{$search}%")
                                ->orWhere('kode', 'like', "%{$search}%");
                        });
                });
            }

            if ($status) {
                $query->where('status', $status);
            }

            $plans = $query->get();

            return response()->json([
                'success' => true,
                'data' => $plans,
                'message' => 'Pencarian rencana produksi berhasil',
                'count' => $plans->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pencarian: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function submit(ProductionPlan $plan)
    {
        DB::beginTransaction();
        try {

            $statusSebelum = $plan->status;

            if ($statusSebelum !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya rencana dengan status draft yang dapat diajukan. Status saat ini: ' . $statusSebelum
                ], 400);
            }

            $plan->update([
                'status' => 'menunggu_persetujuan',
                'diajukan_pada' => now(),
                'updated_at' => now(),
            ]);

            ProductionPlanHistoryService::catatPengajuan($plan);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $plan->fresh(['produk', 'pembuat']),
                'message' => 'Rencana berhasil diajukan untuk persetujuan Manager Produksi'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengajukan rencana: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelSubmission(ProductionPlan $plan)
    {
        DB::beginTransaction();
        try {
            if ($plan->status !== 'menunggu_persetujuan') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya rencana dengan status menunggu persetujuan yang dapat dibatalkan'
                ], 400);
            }

            $plan->update([
                'status' => 'draft',
                'diajukan_pada' => null,
            ]);

            ProductionPlanHistory::create([
                'rencana_id' => $plan->id,
                'user_id' => Auth::id(),
                'aksi' => 'dibatalkan',
                'status_sebelum' => 'menunggu_persetujuan',
                'status_baru' => 'draft',
                'keterangan' => 'Pengajuan rencana dibatalkan oleh PPIC',
                'waktu_aksi' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $plan->fresh(['produk', 'pembuat']),
                'message' => 'Pengajuan rencana berhasil dibatalkan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan pengajuan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function history(ProductionPlan $plan)
    {
        try {
            $histories = $plan->histories()
                ->with('user')
                ->orderBy('waktu_aksi', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $histories,
                'message' => 'History rencana berhasil diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil history: ' . $e->getMessage()
            ], 500);
        }
    }
}
