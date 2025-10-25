<?php

namespace App\Services;

use App\Models\ProductionPlan;
use App\Models\ProductionPlanHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductionPlanHistoryService
{
    /**
     * Catat history untuk rencana produksi
     */
    public static function catatHistory(ProductionPlan $plan, string $aksi, string $statusSebelum, string $statusBaru, ?string $keterangan = null): void
    {
        try {
            ProductionPlanHistory::create([
                'rencana_id' => $plan->id,
                'user_id' => Auth::id(),
                'aksi' => $aksi,
                'status_sebelum' => $statusSebelum,
                'status_baru' => $statusBaru,
                'keterangan' => $keterangan,
                'waktu_aksi' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error recording production plan history: ' . $e->getMessage(), [
                'plan_id' => $plan->id,
                'user_id' => Auth::id(),
                'action' => $aksi
            ]);
        }
    }

    /**
     * Catat pembuatan rencana baru
     */
    public static function catatPembuatan(ProductionPlan $plan): void
    {
        self::catatHistory(
            $plan,
            'dibuat',
            'baru', // Status sebelum
            'draft', // Status baru
            'Rencana produksi dibuat oleh PPIC'
        );
    }

    /**
     * Catat pengajuan persetujuan
     */
    public static function catatPengajuan(ProductionPlan $plan, string $statusSebelum = 'draft'): void
    {
        self::catatHistory(
            $plan,
            'diajukan',
            $statusSebelum, // Status sebelum (harus draft)
            'menunggu_persetujuan', // Status baru
            'Rencana diajukan untuk persetujuan manager produksi'
        );
    }

    /**
     * Catat persetujuan rencana
     */
    public static function catatPersetujuan(ProductionPlan $plan, string $statusSebelum = 'menunggu_persetujuan', ?string $catatan = null): void
    {
        self::catatHistory(
            $plan,
            'disetujui',
            $statusSebelum, // Status sebelum (harus menunggu_persetujuan)
            'disetujui', // Status baru
            $catatan ?? 'Rencana disetujui oleh manager produksi'
        );
    }

    /**
     * Catat penolakan rencana
     */
    public static function catatPenolakan(ProductionPlan $plan, string $statusSebelum = 'menunggu_persetujuan', string $alasan = null): void
    {
        self::catatHistory(
            $plan,
            'ditolak',
            $statusSebelum, // Status sebelum (harus menunggu_persetujuan)
            'ditolak', // Status baru
            'Rencana ditolak: ' . ($alasan ?? 'Tidak ada alasan')
        );
    }

    /**
     * Catat perubahan status menjadi order produksi
     */
    public static function catatMenjadiOrder(ProductionPlan $plan, string $statusSebelum = 'disetujui'): void
    {
        self::catatHistory(
            $plan,
            'diproses',
            $statusSebelum, // Status sebelum (harus disetujui)
            'menjadi_order', // Status baru
            'Rencana diproses menjadi order produksi'
        );
    }

    /**
     * Catat pembatalan pengajuan
     */
    public static function catatPembatalan(ProductionPlan $plan): void
    {
        self::catatHistory(
            $plan,
            'dibatalkan',
            'menunggu_persetujuan', // Status sebelum
            'draft', // Status baru
            'Pengajuan rencana dibatalkan oleh PPIC'
        );
    }

    /**
     * Catat update data rencana
     */
    public static function catatUpdate(ProductionPlan $plan, array $changes, string $statusSebelum): void
    {
        if (!empty($changes)) {
            $keterangan = 'Data rencana diperbarui: ' . implode(', ', array_keys($changes));

            self::catatHistory(
                $plan,
                'diupdate',
                $statusSebelum, // Status sebelum update
                $plan->status, // Status baru (sama, karena hanya update data)
                $keterangan
            );
        }
    }

    /**
     * Catat penghapusan rencana
     */
    public static function catatPenghapusan(ProductionPlan $plan, string $statusSebelum): void
    {
        self::catatHistory(
            $plan,
            'dihapus',
            $statusSebelum, // Status sebelum dihapus
            'dihapus', // Status baru
            'Rencana produksi dihapus oleh PPIC'
        );
    }
}
