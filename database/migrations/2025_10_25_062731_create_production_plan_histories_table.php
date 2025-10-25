<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_plan_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('rencana_id');
            $table->uuid('user_id'); // User yang melakukan aksi
            $table->string('aksi'); // dibuat, menunggu_persetujuan, disetujui, ditolak, diproses
            $table->string('status_sebelum')->nullable();
            $table->string('status_baru');
            $table->text('keterangan')->nullable();
            $table->timestamp('waktu_aksi')->useCurrent();
            $table->timestamps();

            $table->foreign('rencana_id')->references('id')->on('production_plans')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_plan_histories');
    }
};
