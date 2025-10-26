<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductionOrderHistoryController extends Controller
{

    public function getOrderHistory(Request $request, $orderId): JsonResponse
    {
        try {
            Log::info('Fetching order history for order ID: ' . $orderId);

            $order = ProductionOrder::with([
                'produk:id,nama,kode,satuan',
                'pekerja:id,name'
            ])->find($orderId);

            if (!$order) {
                Log::warning('Order not found: ' . $orderId);
                return response()->json([
                    'success' => false,
                    'message' => 'Order produksi tidak ditemukan'
                ], 404);
            }

            $histories = ProductionOrderHistory::with(['changedBy:id,name'])
                ->where('order_id', $orderId)
                ->orderBy('diubah_pada', 'desc')
                ->get();

            Log::info('Found ' . $histories->count() . ' history records for order: ' . $orderId);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'histories' => $histories
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting order history for ' . $orderId . ': ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil history produksi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAllOrders(Request $request): JsonResponse
    {
        try {
            $query = ProductionOrder::with([
                'produk:id,nama,kode,satuan',
                'pekerja:id,name',
                'rencana:id,nomor_rencana'
            ]);

            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            if ($request->has('start_date') && $request->start_date !== '') {
                $startDate = $request->start_date . ' 00:00:00';
                $endDate = $request->has('end_date') && $request->end_date !== ''
                    ? $request->end_date . ' 23:59:59'
                    : now()->format('Y-m-d 23:59:59');

                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            if ($request->has('search') && $request->search !== '') {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('nomor_order', 'like', $searchTerm)
                        ->orWhereHas('produk', function ($q) use ($searchTerm) {
                            $q->where('nama', 'like', $searchTerm)
                                ->orWhere('kode', 'like', $searchTerm);
                        });
                });
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting all orders: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data order produksi'
            ], 500);
        }
    }

    public function getStatistics(): JsonResponse
    {
        try {
            $total = ProductionOrder::count();
            $menunggu = ProductionOrder::where('status', 'menunggu')->count();
            $dalam_proses = ProductionOrder::where('status', 'dalam_proses')->count();
            $selesai = ProductionOrder::where('status', 'selesai')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'menunggu' => $menunggu,
                    'dalam_proses' => $dalam_proses,
                    'selesai' => $selesai
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting statistics: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik order produksi'
            ], 500);
        }
    }

    public function getAllHistories(Request $request): JsonResponse
    {
        try {
            $query = ProductionOrderHistory::with([
                'order:id,nomor_order,target_jumlah,status',
                'changedBy:id,name'
            ]);

            if ($request->has('start_date') && $request->start_date !== '') {
                $startDate = $request->start_date . ' 00:00:00';
                $endDate = $request->has('end_date') && $request->end_date !== ''
                    ? $request->end_date . ' 23:59:59'
                    : now()->format('Y-m-d 23:59:59');

                $query->whereBetween('diubah_pada', [$startDate, $endDate]);
            }

            if ($request->has('order_number') && $request->order_number !== '') {
                $query->whereHas('order', function ($q) use ($request) {
                    $q->where('nomor_order', 'like', '%' . $request->order_number . '%');
                });
            }

            if ($request->has('status') && $request->status !== '') {
                $query->where('status_baru', $request->status);
            }

            $histories = $query->orderBy('diubah_pada', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $histories
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting all histories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data history'
            ], 500);
        }
    }
}
