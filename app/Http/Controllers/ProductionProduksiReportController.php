<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\ProductionReport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductionProduksiReportController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.',
                    'data' => []
                ], 401);
            }

            $query = ProductionReport::with('pembuat')
                ->orderBy('created_at', 'desc');

            if ($request->has('periode_awal') && $request->has('periode_akhir')) {
                $query->whereBetween('periode_awal', [$request->periode_awal, $request->periode_akhir]);
            }

            if (!$request->has('periode_awal')) {
                $startOfMonth = Carbon::now()->startOfMonth();
                $endOfMonth = Carbon::now()->endOfMonth();
                $query->whereBetween('periode_awal', [$startOfMonth, $endOfMonth]);
            }

            $laporan = $query->get();

            return response()->json([
                'success' => true,
                'data' => $laporan,
                'message' => 'Data laporan produksi berhasil diambil'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching production reports: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data laporan: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function generate(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            $request->validate([
                'periode_awal' => 'required|date',
                'periode_akhir' => 'required|date|after_or_equal:periode_awal',
                'catatan' => 'nullable|string'
            ]);

            $nomorLaporan = 'LAP-PROD-' . date('Ymd') . '-' . str_pad(ProductionReport::count() + 1, 4, '0', STR_PAD_LEFT);

            $statistik = $this->calculateProductionStats($request->periode_awal, $request->periode_akhir);

            $laporan = ProductionReport::create([
                'nomor_laporan' => $nomorLaporan,
                'periode_awal' => $request->periode_awal,
                'periode_akhir' => $request->periode_akhir,
                'dibuat_oleh' => $user->id,
                'catatan' => $request->catatan,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'laporan' => $laporan->load('pembuat'),
                    'statistik' => $statistik
                ],
                'message' => 'Laporan produksi berhasil digenerate'
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating production report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateProductionStats($periodeAwal, $periodeAkhir)
    {
        $periodeAwal = Carbon::parse($periodeAwal);
        $periodeAkhir = Carbon::parse($periodeAkhir);

        $totalOrder = DB::table('production_orders')
            ->whereBetween('created_at', [$periodeAwal, $periodeAkhir])
            ->count();

        $orderByStatus = DB::table('production_orders')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$periodeAwal, $periodeAkhir])
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        $totalProduksi = DB::table('production_orders')
            ->whereBetween('updated_at', [$periodeAwal, $periodeAkhir])
            ->where('status', 'selesai')
            ->sum('jumlah_aktual');

        $totalReject = DB::table('production_orders')
            ->whereBetween('updated_at', [$periodeAwal, $periodeAkhir])
            ->where('status', 'selesai')
            ->sum('jumlah_reject');

        $totalTarget = DB::table('production_orders')
            ->whereBetween('created_at', [$periodeAwal, $periodeAkhir])
            ->sum('target_jumlah');

        $efisiensi = $totalTarget > 0 ? round(($totalProduksi / $totalTarget) * 100, 2) : 0;

        $produkStats = DB::table('production_orders as po')
            ->join('master_products as mp', 'po.produk_id', '=', 'mp.id')
            ->select(
                'mp.kode',
                'mp.nama as produk',
                DB::raw('COUNT(po.id) as total_order'),
                DB::raw('SUM(po.jumlah_aktual) as total_produksi'),
                DB::raw('SUM(po.jumlah_reject) as total_reject'),
                DB::raw('SUM(po.target_jumlah) as total_target'),
                DB::raw('CASE WHEN SUM(po.target_jumlah) > 0 THEN ROUND((SUM(po.jumlah_aktual) / SUM(po.target_jumlah)) * 100, 2) ELSE 0 END as efisiensi')
            )
            ->whereBetween('po.created_at', [$periodeAwal, $periodeAkhir])
            ->groupBy('mp.id', 'mp.kode', 'mp.nama')
            ->get();

        $dailyStats = DB::table('production_orders')
            ->select(
                DB::raw('DATE(created_at) as tanggal'),
                DB::raw('COUNT(*) as total_order'),
                DB::raw('SUM(CASE WHEN status = "selesai" THEN jumlah_aktual ELSE 0 END) as produksi_harian'),
                DB::raw('SUM(CASE WHEN status = "selesai" THEN jumlah_reject ELSE 0 END) as reject_harian')
            )
            ->whereBetween('created_at', [$periodeAwal, $periodeAkhir])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('tanggal')
            ->get();

        return [
            'periode' => [
                'awal' => $periodeAwal->format('d/m/Y'),
                'akhir' => $periodeAkhir->format('d/m/Y'),
                'hari' => $periodeAwal->diffInDays($periodeAkhir) + 1
            ],
            'ringkasan' => [
                'total_order' => $totalOrder,
                'total_produksi' => $totalProduksi ?? 0,
                'total_reject' => $totalReject ?? 0,
                'total_target' => $totalTarget ?? 0,
                'efisiensi' => $efisiensi,
                'tingkat_reject' => $totalProduksi > 0 ? round(($totalReject / $totalProduksi) * 100, 2) : 0
            ],
            'status_order' => $orderByStatus,
            'statistik_produk' => $produkStats,
            'statistik_harian' => $dailyStats
        ];
    }

    public function exportExcel($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            while (ob_get_level()) {
                ob_end_clean();
            }

            Log::info('Export Excel started', [
                'user_id' => $user->id,
                'report_id' => $id
            ]);

            $laporan = ProductionReport::with('pembuat')->findOrFail($id);
            $statistik = $this->calculateProductionStats($laporan->periode_awal, $laporan->periode_akhir);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'LAPORAN PRODUKSI');
            $sheet->mergeCells('A1:J1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A2', 'Nomor Laporan: ' . $laporan->nomor_laporan);
            $sheet->mergeCells('A2:J2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A3', 'Periode: ' . $statistik['periode']['awal'] . ' - ' . $statistik['periode']['akhir']);
            $sheet->mergeCells('A3:J3');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A4', 'Tanggal Export: ' . Carbon::now()->format('d/m/Y H:i:s'));
            $sheet->mergeCells('A4:J4');
            $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A5', 'Dibuat Oleh: ' . ($laporan->pembuat->name ?? 'Tidak Diketahui'));
            $sheet->mergeCells('A5:J5');
            $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A7', 'RINGKASAN PRODUKSI');
            $sheet->mergeCells('A7:B7');
            $sheet->getStyle('A7')->getFont()->setBold(true);
            $sheet->getStyle('A7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');

            $sheet->setCellValue('A8', 'Metrik');
            $sheet->setCellValue('B8', 'Nilai');
            $sheet->getStyle('A8:B8')->getFont()->setBold(true);
            $sheet->getStyle('A8:B8')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');
            $sheet->getStyle('A8:B8')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $ringkasanData = [
                ['Total Order', $statistik['ringkasan']['total_order']],
                ['Total Target Produksi', $statistik['ringkasan']['total_target']],
                ['Total Produksi Aktual', $statistik['ringkasan']['total_produksi']],
                ['Total Reject', $statistik['ringkasan']['total_reject']],
                ['Efisiensi Produksi', $statistik['ringkasan']['efisiensi'] . '%'],
                ['Tingkat Reject', $statistik['ringkasan']['tingkat_reject'] . '%']
            ];

            $row = 9;
            foreach ($ringkasanData as $data) {
                $sheet->setCellValue('A' . $row, $data[0]);
                $sheet->setCellValue('B' . $row, $data[1]);
                $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }

            $row += 2;
            $sheet->setCellValue('A' . $row, 'STATUS ORDER');
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');

            $row++;
            $sheet->setCellValue('A' . $row, 'Status');
            $sheet->setCellValue('B' . $row, 'Jumlah');
            $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');
            $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $row++;
            if (!empty($statistik['status_order'])) {
                foreach ($statistik['status_order'] as $status => $jumlah) {
                    $statusLabel = ucfirst(str_replace('_', ' ', $status));
                    $sheet->setCellValue('A' . $row, $statusLabel);
                    $sheet->setCellValue('B' . $row, $jumlah);
                    $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $row++;
                }
            } else {
                $sheet->setCellValue('A' . $row, 'Tidak ada data');
                $sheet->setCellValue('B' . $row, '0');
                $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }

            $sheet->setCellValue('D7', 'STATISTIK PER PRODUK');
            $sheet->mergeCells('D7:J7');
            $sheet->getStyle('D7')->getFont()->setBold(true);
            $sheet->getStyle('D7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');

            $produkHeaders = ['Kode', 'Produk', 'Order', 'Target', 'Produksi', 'Reject', 'Efisiensi'];
            $col = 'D';
            $row = 8;
            foreach ($produkHeaders as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');
                $sheet->getStyle($col . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $col++;
            }

            $row = 9;
            if (!empty($statistik['statistik_produk'])) {
                foreach ($statistik['statistik_produk'] as $produk) {
                    $sheet->setCellValue('D' . $row, $produk->kode ?? '');
                    $sheet->setCellValue('E' . $row, $produk->produk ?? '');
                    $sheet->setCellValue('F' . $row, $produk->total_order ?? 0);
                    $sheet->setCellValue('G' . $row, $produk->total_target ?? 0);
                    $sheet->setCellValue('H' . $row, $produk->total_produksi ?? 0);
                    $sheet->setCellValue('I' . $row, $produk->total_reject ?? 0);
                    $sheet->setCellValue('J' . $row, ($produk->efisiensi ?? 0) . '%');
                    $sheet->getStyle('D' . $row . ':J' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $row++;
                }
            } else {
                $sheet->setCellValue('D' . $row, 'Tidak ada data');
                $sheet->mergeCells('D' . $row . ':J' . $row);
                $sheet->getStyle('D' . $row . ':J' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }

            foreach (range('A', 'J') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $filename = 'Laporan_Produksi_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $laporan->nomor_laporan) . '_' . Carbon::now()->format('YmdHis') . '.xlsx';

            $writer = new Xlsx($spreadsheet);

            $response = new StreamedResponse(function () use ($writer) {
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            return $response;
        } catch (\Exception $e) {
            Log::error('Error exporting production report to Excel: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to export production report: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRealTimeStats()
    {
        try {
            $today = Carbon::today();

            $stats = [
                'harian' => [
                    'order_dibuat' => DB::table('production_orders')
                        ->whereDate('created_at', $today)
                        ->count(),
                    'order_selesai' => DB::table('production_orders')
                        ->whereDate('selesai_pada', $today)
                        ->where('status', 'selesai')
                        ->count(),
                    'produksi_harian' => DB::table('production_orders')
                        ->whereDate('selesai_pada', $today)
                        ->where('status', 'selesai')
                        ->sum('jumlah_aktual'),
                ],
                'bulanan' => [
                    'order_dibuat' => DB::table('production_orders')
                        ->whereMonth('created_at', Carbon::now()->month)
                        ->count(),
                    'order_selesai' => DB::table('production_orders')
                        ->whereMonth('selesai_pada', Carbon::now()->month)
                        ->where('status', 'selesai')
                        ->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting realtime stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => []
            ], 500);
        }
    }

    public function preview($id)
    {
        try {
            $laporan = ProductionReport::with('pembuat')->findOrFail($id);
            $statistik = $this->calculateProductionStats($laporan->periode_awal, $laporan->periode_akhir);

            return response()->json([
                'success' => true,
                'data' => [
                    'laporan' => $laporan,
                    'statistik' => $statistik
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error previewing report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat preview laporan'
            ], 500);
        }
    }
}
