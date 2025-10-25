<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
use App\Models\ProductionOrder;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductionReportController extends Controller
{
    public function index(Request $request)
    {
        try {
            Log::info('Production Report Index Called', ['request' => $request->all()]);

            $startDate = null;
            $endDate = null;
            $tahun = $request->tahun ?? Carbon::now()->year;
            $bulan = $request->bulan ?? Carbon::now()->month;
            $minggu = $request->minggu ?? 1;
            $periode = $request->periode ?? 'bulanan';

            // Tentukan range tanggal berdasarkan periode
            if ($periode === 'bulanan' && $tahun && $bulan) {
                $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
            } elseif ($periode === 'mingguan' && $tahun && $minggu) {
                $startDate = Carbon::now()->setISODate($tahun, $minggu)->startOfWeek();
                $endDate = $startDate->copy()->endOfWeek();
            } else {
                // Default: bulan ini
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            }

            Log::info('Date Range', ['start' => $startDate, 'end' => $endDate]);

            // Query untuk data laporan
            $query = ProductionPlan::with([
                'produk',
                'orderProduksi',
                'histories' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                },
                'pembuat',
                'penyetuju'
            ])
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Filter status jika ada
            if ($request->status) {
                $query->where('status', $request->status);
            }

            $productionPlans = $query->orderBy('created_at', 'desc')->get();

            Log::info('Plans Found', ['count' => $productionPlans->count()]);

            $laporanData = $productionPlans->map(function ($plan) {
                return $this->formatLaporanItem($plan);
            });

            // Hitung statistik
            $statistics = $this->calculateStatistics($laporanData);

            return response()->json([
                'success' => true,
                'data' => [
                    'laporan' => $laporanData,
                    'statistics' => $statistics,
                    'periode' => [
                        'awal' => $startDate->format('Y-m-d'),
                        'akhir' => $endDate->format('Y-m-d'),
                        'jenis' => $periode
                    ]
                ],
                'message' => 'Data laporan berhasil diambil'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProductionReportController@index', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generate(Request $request)
    {
        try {
            Log::info('Generate Report Called', ['request' => $request->all()]);

            $startDate = null;
            $endDate = null;
            $tahun = $request->tahun ?? Carbon::now()->year;
            $bulan = $request->bulan ?? Carbon::now()->month;
            $minggu = $request->minggu ?? 1;
            $periode = $request->periode ?? 'bulanan';

            // Tentukan range tanggal berdasarkan periode
            if ($periode === 'bulanan' && $tahun && $bulan) {
                $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
            } elseif ($periode === 'mingguan' && $tahun && $minggu) {
                $startDate = Carbon::now()->setISODate($tahun, $minggu)->startOfWeek();
                $endDate = $startDate->copy()->endOfWeek();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter periode tidak valid'
                ], 400);
            }

            // Ambil data untuk laporan
            $productionPlans = ProductionPlan::with([
                'produk',
                'orderProduksi',
                'histories',
                'pembuat',
                'penyetuju'
            ])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['disetujui', 'menjadi_order', 'dalam_produksi', 'selesai'])
                ->get();

            $laporanData = $productionPlans->map(function ($plan) {
                return $this->formatLaporanItem($plan);
            });

            $statistics = $this->calculateStatistics($laporanData);

            // Generate nomor laporan virtual
            $nomorLaporan = 'LAP/' .
                ($periode === 'bulanan' ?
                    strtoupper($startDate->format('M')) . '/' . $tahun :
                    'W' . $minggu . '/' . $tahun) .
                '/' . date('His');

            return response()->json([
                'success' => true,
                'data' => [
                    'nomor_laporan' => $nomorLaporan,
                    'laporan' => $laporanData,
                    'statistics' => $statistics,
                    'periode' => [
                        'awal' => $startDate->format('Y-m-d'),
                        'akhir' => $endDate->format('Y-m-d'),
                        'jenis' => $periode
                    ],
                    'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'generated_by' => auth()->user()->name
                ],
                'message' => 'Laporan berhasil digenerate'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProductionReportController@generate', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            // Ambil data berdasarkan filter
            $startDate = null;
            $endDate = null;
            $tahun = $request->tahun ?? Carbon::now()->year;
            $bulan = $request->bulan ?? Carbon::now()->month;
            $minggu = $request->minggu ?? 1;
            $periode = $request->periode ?? 'bulanan';

            // Tentukan range tanggal berdasarkan periode
            if ($periode === 'bulanan' && $tahun && $bulan) {
                $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
            } elseif ($periode === 'mingguan' && $tahun && $minggu) {
                $startDate = Carbon::now()->setISODate($tahun, $minggu)->startOfWeek();
                $endDate = $startDate->copy()->endOfWeek();
            } else {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            }

            // Query data
            $query = ProductionPlan::with([
                'produk',
                'orderProduksi',
                'pembuat',
                'penyetuju'
            ])
                ->whereBetween('created_at', [$startDate, $endDate]);

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $productionPlans = $query->orderBy('created_at', 'desc')->get();

            $laporanData = $productionPlans->map(function ($plan) {
                return $this->formatLaporanItem($plan);
            });

            // Buat spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set judul laporan
            $sheet->setCellValue('A1', 'LAPORAN RENCANA PRODUKSI');
            $sheet->mergeCells('A1:L1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Info periode
            $sheet->setCellValue('A2', 'Periode: ' . $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'));
            $sheet->mergeCells('A2:L2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Tanggal export
            $sheet->setCellValue('A3', 'Tanggal Export: ' . Carbon::now()->format('d/m/Y H:i:s'));
            $sheet->mergeCells('A3:L3');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Header tabel
            $headers = [
                'No',
                'Nomor Rencana',
                'Produk',
                'Kode Produk',
                'Jumlah Rencana',
                'Satuan',
                'Status',
                'Progress',
                'Batas Selesai',
                'Keterlambatan',
                'Dibuat Oleh',
                'Disetujui Oleh',
                'Tanggal Dibuat'
            ];

            $row = 5;
            $col = 'A';

            // Set header
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');
                $sheet->getStyle($col . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $col++;
            }

            // Isi data
            $row = 6;
            $no = 1;

            foreach ($laporanData as $data) {
                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValue('B' . $row, $data['nomor_rencana']);
                $sheet->setCellValue('C' . $row, $data['produk']['nama']);
                $sheet->setCellValue('D' . $row, $data['produk']['kode']);
                $sheet->setCellValue('E' . $row, $data['jumlah_rencana']);
                $sheet->setCellValue('F' . $row, $data['produk']['satuan']);
                $sheet->setCellValue('G' . $row, $this->getStatusLabel($data['status']));
                $sheet->setCellValue('H' . $row, $data['progress'] . '%');
                $sheet->setCellValue('I' . $row, $this->formatExcelDate($data['batas_selesai']));
                $sheet->setCellValue('J' . $row, $data['terlambat'] ? 'Ya' : 'Tidak');
                $sheet->setCellValue('K' . $row, $data['dibuat_oleh']);
                $sheet->setCellValue('L' . $row, $data['disetujui_oleh'] ?? '-');
                $sheet->setCellValue('M' . $row, $this->formatExcelDate($data['tanggal_dibuat']));

                // Style untuk row data
                $style = $sheet->getStyle('A' . $row . ':M' . $row);
                $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $row++;
                $no++;
            }

            // Auto size columns
            foreach (range('A', 'M') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Set nama file
            $bulanLabel = $this->getBulanLabel($bulan);
            $filename = 'Laporan_Rencana_Produksi_' . $periode . '_' . $tahun . '_' .
                ($periode === 'bulanan' ? $bulanLabel : 'Minggu_' . $minggu) . '.xlsx';

            // Stream file ke browser
            $writer = new Xlsx($spreadsheet);

            $response = new StreamedResponse(function () use ($writer) {
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;
        } catch (\Exception $e) {
            Log::error('Error in ProductionReportController@export', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal export laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format data untuk item laporan
     */
    private function formatLaporanItem($plan)
    {
        $progress = $this->calculateProgress($plan->status);
        $terlambat = $plan->batas_selesai && Carbon::now()->gt(Carbon::parse($plan->batas_selesai));

        $jumlah_selesai = $plan->orderProduksi ? ($plan->orderProduksi->jumlah_aktual ?? 0) : 0;
        $jumlah_reject = $plan->orderProduksi ? ($plan->orderProduksi->jumlah_reject ?? 0) : 0;
        $efisiensi = $plan->jumlah > 0 ? (($jumlah_selesai - $jumlah_reject) / $plan->jumlah) * 100 : 0;

        return [
            'id' => $plan->id,
            'nomor_rencana' => $plan->nomor_rencana,
            'nomor_order' => $plan->orderProduksi ? $plan->orderProduksi->nomor_order : null,
            'produk' => [
                'id' => $plan->produk ? $plan->produk->id : null,
                'kode' => $plan->produk ? $plan->produk->kode : 'N/A',
                'nama' => $plan->produk ? $plan->produk->nama : 'Produk Tidak Ditemukan',
                'satuan' => $plan->produk ? $plan->produk->satuan : 'pcs'
            ],
            'jumlah_rencana' => $plan->jumlah,
            'jumlah_selesai' => $jumlah_selesai,
            'jumlah_reject' => $jumlah_reject,
            'efisiensi' => round($efisiensi, 2),
            'status' => $plan->status,
            'progress' => $progress,
            'batas_selesai' => $plan->batas_selesai,
            'terlambat' => $terlambat,
            'dibuat_oleh' => $plan->pembuat ? $plan->pembuat->name : 'N/A',
            'disetujui_oleh' => $plan->penyetuju ? $plan->penyetuju->name : null,
            'catatan' => $plan->catatan,
            'tanggal_dibuat' => $plan->created_at,
            'tanggal_disetujui' => $plan->disetujui_pada,
            'history' => $plan->histories->map(function ($history) {
                return [
                    'aksi' => $history->aksi,
                    'keterangan' => $history->keterangan,
                    'waktu' => $history->waktu_aksi,
                    'user' => $history->user ? $history->user->name : 'N/A'
                ];
            })
        ];
    }

    /**
     * Hitung progress berdasarkan status
     */
    private function calculateProgress($status)
    {
        $progressMap = [
            'draft' => 0,
            'menunggu_persetujuan' => 30,
            'disetujui' => 50,
            'menjadi_order' => 100,
        ];

        return $progressMap[$status] ?? 0;
    }

    /**
     * Hitung statistik laporan
     */
    private function calculateStatistics($laporanData)
    {
        $totalRencana = count($laporanData);
        $totalProduksi = collect($laporanData)->sum('jumlah_selesai');
        $totalReject = collect($laporanData)->sum('jumlah_reject');

        $statusCounts = collect($laporanData)->countBy('status');
        $progressRataRata = $totalRencana > 0 ?
            collect($laporanData)->avg('progress') : 0;

        $efisiensiRataRata = $totalProduksi > 0 ?
            (($totalProduksi - $totalReject) / $totalProduksi) * 100 : 0;

        return [
            'total_rencana' => $totalRencana,
            'total_produksi' => $totalProduksi,
            'total_reject' => $totalReject,
            'efisiensi_rata_rata' => round($efisiensiRataRata, 2),
            'progress_rata_rata' => round($progressRataRata, 2),
            'status_count' => [
                'draft' => $statusCounts['draft'] ?? 0,
                'menunggu_persetujuan' => $statusCounts['menunggu_persetujuan'] ?? 0,
                'disetujui' => $statusCounts['disetujui'] ?? 0,
                'menjadi_order' => $statusCounts['menjadi_order'] ?? 0,
                'ditolak' => $statusCounts['ditolak'] ?? 0
            ]
        ];
    }

    /**
     * Helper: Get label status
     */
    private function getStatusLabel($status)
    {
        $statusLabels = [
            'draft' => 'Draft',
            'menunggu_persetujuan' => 'Menunggu Persetujuan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'menjadi_order' => 'Menjadi Order'
        ];

        return $statusLabels[$status] ?? $status;
    }

    /**
     * Helper: Format date untuk Excel
     */
    private function formatExcelDate($dateString)
    {
        if (!$dateString) return '-';
        return Carbon::parse($dateString)->format('d/m/Y');
    }

    /**
     * Helper: Get label bulan
     */
    private function getBulanLabel($bulan)
    {
        $bulanList = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        return $bulanList[$bulan] ?? 'Unknown';
    }
}
