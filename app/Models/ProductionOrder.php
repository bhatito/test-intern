<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProductionOrder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'production_orders';
    protected $fillable = [
        'nomor_order',
        'rencana_id',
        'produk_id',
        'target_jumlah',
        'jumlah_aktual',
        'jumlah_reject',
        'status',
        'mulai_pada',
        'selesai_pada',
        'dikerjakan_oleh'
    ];
    public $incrementing = false;
    protected $keyType = 'string';

    // Rencana asal order
    public function rencana()
    {
        return $this->belongsTo(ProductionPlan::class, 'rencana_id');
    }

    // Produk yang dikerjakan
    public function produk()
    {
        return $this->belongsTo(MasterProduct::class, 'produk_id');
    }

    // Staff produksi yang mengerjakan
    public function pekerja()
    {
        return $this->belongsTo(User::class, 'dikerjakan_oleh');
    }

    // Histori perubahan status
    public function historiStatus()
    {
        return $this->hasMany(ProductionOrderHistory::class, 'order_id');
    }

    // Data reject (cacat)
    public function dataReject()
    {
        return $this->hasMany(ProductionReject::class, 'order_id');
    }
}
