<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardProduksiController extends Controller
{
    /**
     * Menampilkan dashboard produksi
     */
    public function index()
    {
        try {
            $user = Auth::user();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard produksi berhasil dimuat',
                'data' => [
                    'user' => [
                        'name' => $user->name,
                        'role' => $user->role,
                        'department' => $user->department
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat dashboard: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Mendapatkan statistik dashboard produksi
     */
    public function getDashboardStats()
    {
        try {
            $user = Auth::user();
            Log::info('Getting dashboard stats for user: ' . $user->id . ' with role: ' . $user->role);

            $stats = $this->calculateDashboardStats($user);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard stats error: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'data' => $this->getDefaultStats()
            ]);
        }
    }

    /**
     * Menghitung statistik dashboard - SAMA untuk semua role
     */
    private function calculateDashboardStats($user)
    {
        $stats = $this->getDefaultStats();

        try {
            // Untuk semua role, tampilkan data yang sama dari production_orders
            $baseQuery = DB::table('production_orders');
            // $plant = DB::table('production_plans');

            $stats = [
                'total' => (clone $baseQuery)->count(),
                'menunggu' => (clone $baseQuery)->where('status', 'menunggu')->count(),
                'dalam_proses' => (clone $baseQuery)->where('status', 'dalam_proses')->count(),
                'selesai' => (clone $baseQuery)->where('status', 'selesai')->count(),
                'selesai_bulan_ini' => (clone $baseQuery)
                    ->where('status', 'selesai')
                    ->whereYear('updated_at', now()->year)
                    ->whereMonth('updated_at', now()->month)
                    ->count()
            ];

            Log::info('Calculated stats for user ' . $user->id . ': ' . json_encode($stats));
            return $stats;
        } catch (\Exception $e) {
            Log::error('Calculate stats error: ' . $e->getMessage());
            return $this->getDefaultStats();
        }
    }

    /**
     * Mendapatkan jumlah persetujuan yang pending (untuk notifikasi)
     */
    public function getPendingApprovalsCount()
    {
        try {


            $count = DB::table('production_plans')
                ->where('status', 'menunggu_persetujuan')
                ->count();

            Log::info('Pending approvals count for manager: ' . $count);

            return response()->json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Pending approvals error: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'count' => 0
            ]);
        }
    }

    /**
     * Mendapatkan jumlah order yang sudah disetujui/dikerjakan
     */
    public function getApprovedOrdersCount()
    {
        try {

            // Untuk semua role, hitung order yang selesai
            $count = DB::table('production_plans')
                ->where('status', 'menunggu_persetujuan')
                ->count();

            Log::info('Approved orders count: ' . $count);

            return response()->json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Approved orders error: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'count' => 0
            ]);
        }
    }

    /**
     * Default stats ketika data tidak tersedia
     */
    private function getDefaultStats()
    {
        return [
            'total' => 0,
            'menunggu' => 0,
            'dalam_proses' => 0,
            'selesai' => 0,
            'selesai_bulan_ini' => 0
        ];
    }


    public function pendingCount(Request $request)
    {
        $count = ProductionOrder::where('status', 'menunggu')->count();
        return response()->json(['count' => $count]);
    }
}
