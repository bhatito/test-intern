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
        Schema::create('production_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nomor_rencana')->unique();
            $table->uuid('produk_id');
            $table->integer('jumlah');
            $table->uuid('dibuat_oleh'); // staff PPIC
            $table->uuid('disetujui_oleh')->nullable(); // manager produksi
            $table->enum('status', ['draft', 'menunggu_persetujuan', 'disetujui', 'ditolak', 'menjadi_order'])->default('draft');
            $table->date('batas_selesai')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('disetujui_pada')->nullable();
            $table->timestamp('ditolak_pada')->nullable();
            $table->timestamps();

            $table->foreign('produk_id')->references('id')->on('master_products')->cascadeOnDelete();
            $table->foreign('dibuat_oleh')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('disetujui_oleh')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_plans');
    }
};
