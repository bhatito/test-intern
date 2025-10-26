<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionOrderController extends Controller
{

    public function index(Request $request)
    {
        try {
            $status = $request->query('status');
            $query = ProductionOrder::with(['produk', 'rencana', 'pekerja'])
                ->orderBy('created_at', 'desc');

            if ($status && in_array($status, ['menunggu', 'dalam_proses', 'selesai'])) {
                $query->where('status', $status);
            }

            $orders = $query->get();

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Data order produksi berhasil diambil',
                'count' => $orders->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data order: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function start(ProductionOrder $order)
    {
        DB::beginTransaction();
        try {
            if ($order->status !== 'menunggu') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak dapat dimulai. Status saat ini: ' . $order->status
                ], 400);
            }

            $order->update([
                'status' => 'dalam_proses',
                'mulai_pada' => now(),
                'dikerjakan_oleh' => Auth::id(),
            ]);
            $statusSebelumnya = 'menunggu';

            ProductionOrderHistory::create([
                'order_id' => $order->id,
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => 'dalam_proses',
                'diubah_oleh' => Auth::id(),
                'keterangan' => 'Memulai proses produksi',
                'diubah_pada' => now(),
            ]);

            DB::commit();

            $order->load(['produk', 'rencana', 'pekerja']);

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order produksi berhasil dimulai'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error starting production order: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memulai order produksi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function complete(Request $request, ProductionOrder $order)
    {
        DB::beginTransaction();
        try {
            if ($order->status !== 'dalam_proses') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak dapat diselesaikan. Status saat ini: ' . $order->status
                ], 400);
            }

            $validated = $request->validate([
                'jumlah_aktual' => 'required|integer|min:0',
                'jumlah_reject' => 'required|integer|min:0',
                'catatan' => 'nullable|string|max:500',
            ]);

            if ($validated['jumlah_reject'] > $validated['jumlah_aktual']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah reject tidak boleh melebihi jumlah aktual'
                ], 422);
            }

            $statusSebelumnya = $order->status;
            $order->update([
                'status' => 'selesai',
                'selesai_pada' => now(),
                'jumlah_aktual' => $validated['jumlah_aktual'],
                'jumlah_reject' => $validated['jumlah_reject'],
                'catatan' => $validated['catatan'] ?? null,
            ]);

            ProductionOrderHistory::create([
                'order_id' => $order->id,
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => 'selesai',
                'diubah_oleh' => Auth::id(),
                'keterangan' => 'Selesai proses produksi',
                'diubah_pada' => now(),
            ]);

            DB::commit();

            $order->load(['produk', 'rencana', 'pekerja']);

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order produksi berhasil diselesaikan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error completing production order: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan order produksi'
            ], 500);
        }
    }

    public function show(ProductionOrder $order)
    {
        try {
            $order->load(['produk', 'rencana', 'pekerja', 'historiStatus.user']);

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Detail order produksi berhasil diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stats()
    {
        try {
            $stats = [
                'total' => ProductionOrder::count(),
                'menunggu' => ProductionOrder::where('status', 'menunggu')->count(),
                'dalam_proses' => ProductionOrder::where('status', 'dalam_proses')->count(),
                'selesai' => ProductionOrder::where('status', 'selesai')->count(),
                'selesai_bulan_ini' => ProductionOrder::where('status', 'selesai')
                    ->whereMonth('selesai_pada', now()->month)
                    ->whereYear('selesai_pada', now()->year)
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistik berhasil diambil'
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

            $query = ProductionOrder::with(['produk', 'rencana', 'pekerja'])
                ->orderBy('created_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nomor_order', 'like', "%{$search}%")
                        ->orWhereHas('produk', function ($q) use ($search) {
                            $q->where('nama', 'like', "%{$search}%");
                        });
                });
            }

            if ($status && in_array($status, ['menunggu', 'dalam_proses', 'selesai'])) {
                $query->where('status', $status);
            }

            $orders = $query->get();

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Pencarian berhasil',
                'count' => $orders->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pencarian: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}
