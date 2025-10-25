<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\ProductionPlan;
use Illuminate\Http\Request;

class DashboardPPICController extends Controller
{
    public function index(Request $request)
    {
        try {
            $totalProduk = MasterProduct::count();
            $totalRencana = ProductionPlan::count();

            $rencanaDraft = ProductionPlan::where('status', 'draft')->count();
            $rencanaMenunggu = ProductionPlan::where('status', 'menunggu_persetujuan')->count();
            $rencanaDisetujui = ProductionPlan::where('status', 'disetujui')->count();
            $rencanaMenjadiOrder = ProductionPlan::where('status', 'menjadi_order')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'totalProduk' => $totalProduk,
                    'totalRencana' => $totalRencana,
                    'rencanaDraft' => $rencanaDraft,
                    'rencanaMenunggu' => $rencanaMenunggu,
                    'rencanaDisetujui' => $rencanaDisetujui,
                    'rencanaMenjadiOrder' => $rencanaMenjadiOrder
                ],
                'message' => 'Statistik dashboard berhasil diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik: ' . $e->getMessage()
            ], 500);
        }
    }
}
