<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProductionPlan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'production_plans';
    protected $fillable = [
        'nomor_rencana',
        'produk_id',
        'jumlah',
        'dibuat_oleh',
        'disetujui_oleh',
        'status',
        'batas_selesai',
        'catatan',
        'disetujui_pada',
        'ditolak_pada'
    ];
    public $incrementing = false;
    protected $keyType = 'string';

    // Produk yang direncanakan
    public function produk()
    {
        return $this->belongsTo(MasterProduct::class, 'produk_id');
    }

    // Pembuat rencana (Staff PPIC)
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    // Penyetuju (Manager Produksi)
    public function penyetuju()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    // Order produksi yang dihasilkan dari rencana ini
    public function orderProduksi()
    {
        return $this->hasOne(ProductionOrder::class, 'rencana_id');
    }
}
