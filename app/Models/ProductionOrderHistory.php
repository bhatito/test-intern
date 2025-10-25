<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProductionOrderHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'production_order_histories';
    protected $fillable = ['order_id', 'status_sebelumnya', 'status_baru', 'diubah_oleh', 'keterangan', 'diubah_pada'];
    public $incrementing = false;
    protected $keyType = 'string';

    // Order terkait
    public function order()
    {
        return $this->belongsTo(ProductionOrder::class, 'order_id');
    }

    // User yang mengubah status
    public function pengubah()
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }
}
