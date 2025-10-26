<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ManagerApprovalController;
use App\Http\Controllers\Api\MasterProductController;
use App\Http\Controllers\Api\ProductionOrderController;
use App\Http\Controllers\Api\ProductionPlanController;
use App\Http\Controllers\DashboardPPICController;
use App\Http\Controllers\DashboardProduksiController;
use App\Http\Controllers\ProductionProduksiReportController;
use App\Http\Controllers\ProductionReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Login umum
Route::post('/login', [AuthController::class, 'login']);

// Group dengan auth sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // 🔸 Khusus PPIC
    // Di routes/api.php - PERBAIKI ROUTE PPIC
    Route::prefix('ppic')->middleware('department:ppic')->group(function () {
        // Route::get('/dashboard', fn() => response()->json(['message' => 'Selamat datang di dashboard PPIC']));

        Route::get('/dashboard', [DashboardPPICController::class, 'index']);


        // Master Products
        Route::apiResource('master-products', MasterProductController::class);

        // Production Plans
        Route::get('/production-plans', [ProductionPlanController::class, 'index']);
        Route::get('/production-plans/statistics', [ProductionPlanController::class, 'statistics']);
        Route::post('/production-plans', [ProductionPlanController::class, 'store']);
        Route::get('/production-plans/{plan}', [ProductionPlanController::class, 'show']);
        Route::put('/production-plans/{plan}', [ProductionPlanController::class, 'update']);
        Route::delete('/production-plans/{plan}', [ProductionPlanController::class, 'destroy']);
        Route::put('/production-plans/{plan}/submit', [ProductionPlanController::class, 'submit']);
        Route::put('/production-plans/{plan}/cancel', [ProductionPlanController::class, 'cancelSubmission']); // PERBAIKI INI
        Route::get('/production-plans/{plan}/history', [ProductionPlanController::class, 'history']);

        Route::get('/production-reports', [ProductionReportController::class, 'index']);
        Route::post('/production-reports/generate', [ProductionReportController::class, 'generate']);
        Route::get('/production-reports/export', [ProductionReportController::class, 'export']);
    });

    // routes/api.php

    // 🔸 Khusus Produksi
    Route::prefix('produksi')->middleware('department:produksi')->group(function () {
        // Dashboard Produksi
        Route::get('/dashboard', [DashboardProduksiController::class, 'index']);
        Route::get('/dashboard/stats', [DashboardProduksiController::class, 'getDashboardStats']);
        Route::get('/dashboard/pending-approvals-count', [DashboardProduksiController::class, 'getPendingApprovalsCount']);
        Route::get('/dashboard/approved-orders-count', [DashboardProduksiController::class, 'getApprovedOrdersCount']);

        // Manager Approvals
        Route::get('/manager/approvals', [ManagerApprovalController::class, 'index']);
        Route::put('/manager/approvals/{plan}', [ManagerApprovalController::class, 'update']);
        Route::get('/manager/approvals/stats', [ManagerApprovalController::class, 'stats']);

        // Production Orders
        Route::get('/production-orders', [ProductionOrderController::class, 'index']);
        Route::get('/production-orders/stats', [ProductionOrderController::class, 'stats']);
        Route::get('/production-orders/search', [ProductionOrderController::class, 'search']);
        Route::get('/production-orders/{order}', [ProductionOrderController::class, 'show']);
        Route::put('/production-orders/{order}/start', [ProductionOrderController::class, 'start']);
        Route::put('/production-orders/{order}/complete', [ProductionOrderController::class, 'complete']);
        Route::put('/production-orders/{order}/progress', [ProductionOrderController::class, 'updateProgress']);

        Route::get('/laporan', [ProductionProduksiReportController::class, 'index']);
        Route::post('/laporan/generate', [ProductionProduksiReportController::class, 'generate']);
        Route::get('/laporan/{id}/export-excel', [ProductionProduksiReportController::class, 'exportExcel']);
        Route::get('/laporan/{id}/preview', [ProductionProduksiReportController::class, 'preview']); // Tambahkan ini
        Route::get('/laporan/stats/realtime', [ProductionProduksiReportController::class, 'getRealTimeStats']);
        // Route::get('/laporan/{id}/export-excel', [ProductionProduksiReportController::class, 'exportExcelSimple']);
    });
});
