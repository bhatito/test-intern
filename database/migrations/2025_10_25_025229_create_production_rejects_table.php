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
        Schema::create('production_rejects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->string('jenis_cacat');
            $table->integer('jumlah');
            $table->uuid('dicatat_oleh');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('production_orders')->cascadeOnDelete();
            $table->foreign('dicatat_oleh')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_rejects');
    }
};
