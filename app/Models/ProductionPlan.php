<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProductionPlan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'production_plans';
    public $incrementing = false;
    protected $keyType = 'string';

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
        'ditolak_pada',
    ];

    protected $casts = [
        'batas_selesai' => 'date',
        'disetujui_pada' => 'datetime',
        'ditolak_pada' => 'datetime',
    ];

    // ✅ Auto nomor rencana
    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->nomor_rencana) {
                $last = self::latest('created_at')->first();
                $num = $last ? intval(substr($last->nomor_rencana, -4)) + 1 : 1;
                $model->nomor_rencana = 'RPN-' . str_pad($num, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /** 🔗 Relasi ke produk */
    public function produk()
    {
        return $this->belongsTo(MasterProduct::class, 'produk_id');
    }

    /** 🔗 Pembuat rencana (staff PPIC) */
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** 🔗 Penyetuju (manager produksi) */
    public function penyetuju()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    /** 🔗 Order produksi yang dihasilkan */
    public function orderProduksi()
    {
        return $this->hasOne(ProductionOrder::class, 'rencana_id');
    }

    /** 🧠 Accessor label status */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'menunggu_persetujuan' => 'Menunggu Persetujuan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'menjadi_order' => 'Sudah Jadi Order Produksi',
            default => ucfirst($this->status),
        };
    }

    /** 🔍 Scope helper */
    public function scopeMenungguPersetujuan($query)
    {
        return $query->where('status', 'menunggu_persetujuan');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'disetujui');
    }
}
