<?php
// app/Services/ProductionOrderHistoryService.php

namespace App\Services;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductionOrderHistoryService
{
    /**
     * Catat history untuk order produksi
     */
    public static function catatHistory(ProductionOrder $order, string $statusSebelum, string $statusBaru, ?string $keterangan = null): void
    {
        try {
            ProductionOrderHistory::create([
                'order_id' => $order->id,
                'status_sebelumnya' => $statusSebelum,
                'status_baru' => $statusBaru,
                'diubah_oleh' => Auth::id(),
                'keterangan' => $keterangan,
                'diubah_pada' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error recording production order history: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'status_sebelum' => $statusSebelum,
                'status_baru' => $statusBaru
            ]);
        }
    }

    /**
     * Catat pembuatan order baru
     */
    public static function catatPembuatan(ProductionOrder $order): void
    {
        self::catatHistory(
            $order,
            null,
            'menunggu',
            'Order produksi dibuat dari rencana produksi'
        );
    }

    /**
     * Catat mulai produksi
     */
    public static function catatMulaiProduksi(ProductionOrder $order, string $statusSebelum = 'menunggu'): void
    {
        self::catatHistory(
            $order,
            $statusSebelum,
            'dalam_proses',
            'Order produksi mulai dikerjakan'
        );
    }

    /**
     * Catat penyelesaian produksi
     */
    public static function catatSelesaiProduksi(ProductionOrder $order, string $statusSebelum = 'dalam_proses'): void
    {
        self::catatHistory(
            $order,
            $statusSebelum,
            'selesai',
            'Order produksi telah selesai'
        );
    }

    /**
     * Catat penutupan order
     */
    public static function catatPenutupan(ProductionOrder $order, string $statusSebelum = 'selesai'): void
    {
        self::catatHistory(
            $order,
            $statusSebelum,
            'ditutup',
            'Order produksi telah ditutup'
        );
    }

    /**
     * Catat update progress produksi
     */
    public static function catatUpdateProgress(ProductionOrder $order, int $progress, string $statusSebelum): void
    {
        self::catatHistory(
            $order,
            $statusSebelum,
            $order->status, // Status tetap sama
            'Progress produksi diupdate menjadi ' . $progress . '%'
        );
    }
}
