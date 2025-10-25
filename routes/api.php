<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ManagerApprovalController;
use App\Http\Controllers\Api\MasterProductController;
use App\Http\Controllers\Api\ProductionPlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Login umum
Route::post('/login', [AuthController::class, 'login']);

// Group dengan auth sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // 🔸 Khusus PPIC
    Route::middleware('department:ppic')->group(function () {
        Route::get('/ppic/dashboard', fn() => response()->json(['message' => 'Selamat datang di dashboard PPIC']));
        Route::apiResource('master-products', MasterProductController::class);
        Route::get('/production-plans', [ProductionPlanController::class, 'index']);
        Route::post('/production-plans', [ProductionPlanController::class, 'store']);
        Route::delete('/production-plans/{plan}', [ProductionPlanController::class, 'destroy']);
    });

    // 🔸 Khusus Produksi
    Route::middleware('department:produksi')->group(function () {
        Route::get('/produksi/dashboard', fn() => response()->json(['message' => 'Selamat datang di dashboard Produksi']));
        Route::get('/manager/approvals', [ManagerApprovalController::class, 'index']);
        Route::put('/manager/approvals/{plan}', [ManagerApprovalController::class, 'update']);
    });
});
