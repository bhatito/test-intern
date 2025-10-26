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
use Laravel\Sanctum\PersonalAccessToken;

class ProductionProduksiReportController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            $query = ProductionReport::with('pembuat')
                ->orderBy('created_at', 'desc');

            // Filter berdasarkan periode jika ada
            if ($request->has('periode_awal') && $request->has('periode_akhir')) {
                $query->whereBetween('periode_awal', [$request->periode_awal, $request->periode_akhir]);
            }

            // Filter bulan ini secara default
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

    /**
     * Generate laporan produksi baru
     */
    public function generate(Request $request)
    {
        try {
            $user = Auth::user();

            $request->validate([
                'periode_awal' => 'required|date',
                'periode_akhir' => 'required|date|after_or_equal:periode_awal',
                'catatan' => 'nullable|string'
            ]);

            // Generate nomor laporan
            $nomorLaporan = 'LAP-PROD-' . date('Ymd') . '-' . str_pad(ProductionReport::count() + 1, 4, '0', STR_PAD_LEFT);

            // Hitung statistik produksi
            $statistik = $this->calculateProductionStats($request->periode_awal, $request->periode_akhir);

            // Buat laporan
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

    /**
     * Menghitung statistik produksi
     */
    private function calculateProductionStats($periodeAwal, $periodeAkhir)
    {
        $periodeAwal = Carbon::parse($periodeAwal);
        $periodeAkhir = Carbon::parse($periodeAkhir);

        // Total order dalam periode
        $totalOrder = DB::table('production_orders')
            ->whereBetween('created_at', [$periodeAwal, $periodeAkhir])
            ->count();

        // Order berdasarkan status
        $orderByStatus = DB::table('production_orders')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$periodeAwal, $periodeAkhir])
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        // Total produksi (jumlah aktual)
        $totalProduksi = DB::table('production_orders')
            ->whereBetween('updated_at', [$periodeAwal, $periodeAkhir])
            ->where('status', 'selesai')
            ->sum('jumlah_aktual');

        // Total reject
        $totalReject = DB::table('production_orders')
            ->whereBetween('updated_at', [$periodeAwal, $periodeAkhir])
            ->where('status', 'selesai')
            ->sum('jumlah_reject');

        // Efisiensi produksi
        $totalTarget = DB::table('production_orders')
            ->whereBetween('created_at', [$periodeAwal, $periodeAkhir])
            ->sum('target_jumlah');

        $efisiensi = $totalTarget > 0 ? round(($totalProduksi / $totalTarget) * 100, 2) : 0;

        // Data per produk
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

        // Data harian untuk chart
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
            // Cek authentication dengan multiple methods
            $user = Auth::user();

            // Jika tidak ada user dari Auth, cek token dari query parameter
            if (!$user) {
                $token = request()->query('token');
                if ($token) {
                    $personalAccessToken = PersonalAccessToken::findToken($token);
                    if ($personalAccessToken) {
                        $user = $personalAccessToken->tokenable;
                        Auth::setUser($user);
                    }
                }
            }

            // Jika masih tidak ada user, return error
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            Log::info('Export Excel started', [
                'user_id' => $user->id,
                'report_id' => $id
            ]);

            $laporan = ProductionReport::with('pembuat')->findOrFail($id);
            $statistik = $this->calculateProductionStats($laporan->periode_awal, $laporan->periode_akhir);

            // Buat spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set judul laporan - SIMPLIFIED VERSION
            $sheet->setCellValue('A1', 'LAPORAN PRODUKSI');
            $sheet->mergeCells('A1:F1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Informasi dasar laporan
            $sheet->setCellValue('A3', 'Nomor Laporan:');
            $sheet->setCellValue('B3', $laporan->nomor_laporan);
            $sheet->setCellValue('A4', 'Periode:');
            $sheet->setCellValue('B4', $statistik['periode']['awal'] . ' - ' . $statistik['periode']['akhir']);
            $sheet->setCellValue('A5', 'Dibuat Oleh:');
            $sheet->setCellValue('B5', $laporan->pembuat->name ?? 'Tidak Diketahui');
            $sheet->setCellValue('A6', 'Tanggal Dibuat:');
            $sheet->setCellValue('B6', $laporan->created_at->format('d/m/Y H:i'));

            // Ringkasan Produksi - Header
            $sheet->setCellValue('A8', 'RINGKASAN PRODUKSI');
            $sheet->mergeCells('A8:B8');
            $sheet->getStyle('A8')->getFont()->setBold(true);

            // Ringkasan data
            $ringkasanLabels = [
                'Total Order',
                'Total Target Produksi',
                'Total Produksi Aktual',
                'Total Reject',
                'Efisiensi Produksi',
                'Tingkat Reject'
            ];

            $ringkasanValues = [
                $statistik['ringkasan']['total_order'],
                $statistik['ringkasan']['total_target'],
                $statistik['ringkasan']['total_produksi'],
                $statistik['ringkasan']['total_reject'],
                $statistik['ringkasan']['efisiensi'] . '%',
                $statistik['ringkasan']['tingkat_reject'] . '%'
            ];

            $row = 9;
            foreach ($ringkasanLabels as $index => $label) {
                $sheet->setCellValue('A' . $row, $label);
                $sheet->setCellValue('B' . $row, $ringkasanValues[$index]);
                $row++;
            }

            // Statistik per Status
            $row += 2;
            $sheet->setCellValue('A' . $row, 'STATUS ORDER');
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);

            $row++;
            $sheet->setCellValue('A' . $row, 'Status');
            $sheet->setCellValue('B' . $row, 'Jumlah');
            $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);

            $row++;
            if (!empty($statistik['status_order'])) {
                foreach ($statistik['status_order'] as $status => $jumlah) {
                    $statusLabel = ucfirst(str_replace('_', ' ', $status));
                    $sheet->setCellValue('A' . $row, $statusLabel);
                    $sheet->setCellValue('B' . $row, $jumlah);
                    $row++;
                }
            } else {
                $sheet->setCellValue('A' . $row, 'Tidak ada data');
                $sheet->setCellValue('B' . $row, '0');
                $row++;
            }

            // Statistik per Produk
            $sheet->setCellValue('D8', 'STATISTIK PER PRODUK');
            $sheet->mergeCells('D8:H8');
            $sheet->getStyle('D8')->getFont()->setBold(true);

            $produkHeaders = ['Kode', 'Produk', 'Order', 'Target', 'Produksi', 'Reject', 'Efisiensi'];
            $col = 'D';
            foreach ($produkHeaders as $header) {
                $sheet->setCellValue($col . '9', $header);
                $sheet->getStyle($col . '9')->getFont()->setBold(true);
                $col++;
            }

            $row = 10;
            if (!empty($statistik['statistik_produk'])) {
                foreach ($statistik['statistik_produk'] as $produk) {
                    $sheet->setCellValue('D' . $row, $produk->kode ?? '');
                    $sheet->setCellValue('E' . $row, $produk->produk ?? '');
                    $sheet->setCellValue('F' . $row, $produk->total_order ?? 0);
                    $sheet->setCellValue('G' . $row, $produk->total_target ?? 0);
                    $sheet->setCellValue('H' . $row, $produk->total_produksi ?? 0);
                    $sheet->setCellValue('I' . $row, $produk->total_reject ?? 0);
                    $sheet->setCellValue('J' . $row, ($produk->efisiensi ?? 0) . '%');
                    $row++;
                }
            } else {
                $sheet->setCellValue('D' . $row, 'Tidak ada data');
                $sheet->mergeCells('D' . $row . ':J' . $row);
            }

            // Auto size columns
            foreach (range('A', 'J') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Set nama file
            $filename = 'Laporan_Produksi_' . $laporan->nomor_laporan . '.xlsx';

            // Stream file ke browser - FIXED VERSION
            $response = new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'max-age=0');
            $response->headers->set('Pragma', 'public');

            Log::info('Export Excel completed successfully');

            return $response;
        } catch (\Exception $e) {
            Log::error('Error exporting production report to Excel: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Gagal export laporan produksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistik real-time untuk dashboard
     */
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
            ]);
        }
    }

    /**
     * Preview laporan (untuk modal detail)
     */
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
