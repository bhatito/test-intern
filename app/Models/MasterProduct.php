<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MasterProduct extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'master_products';
    protected $fillable = ['kode', 'nama', 'satuan', 'deskripsi'];
    public $incrementing = false;
    protected $keyType = 'string';

    // Relasi ke Rencana Produksi
    public function rencanaProduksi()
    {
        return $this->hasMany(ProductionPlan::class, 'produk_id');
    }

    // Relasi ke Order Produksi
    public function orderProduksi()
    {
        return $this->hasMany(ProductionOrder::class, 'produk_id');
    }
}
