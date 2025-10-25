<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nomor_order')->unique();
            $table->uuid('rencana_id');
            $table->uuid('produk_id');
            $table->integer('target_jumlah');
            $table->integer('jumlah_aktual')->nullable();
            $table->integer('jumlah_reject')->nullable();
            $table->enum('status', ['menunggu', 'dalam_proses', 'selesai', 'ditutup'])->default('menunggu');
            $table->timestamp('mulai_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->uuid('dikerjakan_oleh')->nullable(); // staff produksi
            $table->timestamps();

            $table->foreign('rencana_id')->references('id')->on('production_plans')->cascadeOnDelete();
            $table->foreign('produk_id')->references('id')->on('master_products')->cascadeOnDelete();
            $table->foreign('dikerjakan_oleh')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
