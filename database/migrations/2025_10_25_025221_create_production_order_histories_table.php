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
        Schema::create('production_order_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->string('status_sebelumnya')->nullable();
            $table->string('status_baru');
            $table->uuid('diubah_oleh');
            $table->text('keterangan')->nullable();
            $table->timestamp('diubah_pada')->useCurrent();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('production_orders')->cascadeOnDelete();
            $table->foreign('diubah_oleh')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_order_histories');
    }
};
