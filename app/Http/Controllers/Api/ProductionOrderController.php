<?php

// app/Http/Controllers/Api/ProductionOrderController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionOrderController extends Controller
{
    /** 📋 List order untuk produksi (optional filter status) */
    public function index(Request $request)
    {
        $status = $request->query('status'); // menunggu|dikerjakan|selesai|null
        $q = ProductionOrder::with(['produk', 'rencana', 'pekerja'])
            ->orderBy('created_at', 'desc');

        if ($status) $q->where('status', $status);
        return response()->json($q->get());
    }

    /** ▶️ Mulai produksi: set status = dikerjakan, set mulai_pada */
    public function start(ProductionOrder $order)
    {
        if ($order->status !== 'menunggu') {
            return response()->json(['message' => 'Order bukan status menunggu.'], 400);
        }

        $order->update([
            'status' => 'dikerjakan',
            'mulai_pada' => now(),
            'dikerjakan_oleh' => Auth::id(),
        ]);

        ProductionOrderHistory::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'status' => 'dikerjakan',
            'catatan' => 'Mulai produksi',
            'waktu_perubahan' => now(),
        ]);

        return response()->json($order->fresh(['produk', 'rencana', 'pekerja']));
    }

    /** ⏹️ Selesaikan produksi: isi jumlah aktual & reject; set status = selesai, set selesai_pada */
    public function complete(Request $request, ProductionOrder $order)
    {
        if ($order->status !== 'dikerjakan') {
            return response()->json(['message' => 'Order belum dalam status dikerjakan.'], 400);
        }

        $validated = $request->validate([
            'jumlah_aktual' => 'required|integer|min:0',
            'jumlah_reject' => 'required|integer|min:0',
            'catatan' => 'nullable|string',
        ]);

        $order->update([
            'status' => 'selesai',
            'selesai_pada' => now(),
            'jumlah_aktual' => $validated['jumlah_aktual'],
            'jumlah_reject' => $validated['jumlah_reject'],
        ]);

        ProductionOrderHistory::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'status' => 'selesai',
            'catatan' => $validated['catatan'] ?? null,
            'waktu_perubahan' => now(),
        ]);

        return response()->json($order->fresh(['produk', 'rencana', 'pekerja']));
    }

    /** ℹ️ Detail order + histori  */
    public function show(ProductionOrder $order)
    {
        $order->load(['produk', 'rencana', 'pekerja', 'historiStatus.user', 'dataReject']);
        return response()->json($order);
    }

    /** 📈 Statistik ringkas untuk dashboard Produksi (opsional) */
    public function stats()
    {
        return response()->json([
            'total' => ProductionOrder::count(),
            'dikerjakan' => ProductionOrder::where('status', 'dikerjakan')->count(),
            'selesai_bulan_ini' => ProductionOrder::where('status', 'selesai')
                ->whereMonth('selesai_pada', now()->month)
                ->whereYear('selesai_pada', now()->year)
                ->count(),
        ]);
    }
}
