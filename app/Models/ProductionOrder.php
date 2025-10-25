<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

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
        'dikerjakan_oleh',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'mulai_pada' => 'datetime',
        'selesai_pada' => 'datetime',
    ];

    /**
     * 🔹 Generate UUID & nomor order otomatis
     */
    protected static function booted()
    {
        static::creating(function ($order) {
            if (!$order->nomor_order) {
                $latest = self::latest('created_at')->first();
                $no = $latest ? intval(substr($latest->nomor_order, -4)) + 1 : 1;
                $order->nomor_order = 'ORD-' . str_pad($no, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * 🔗 Relasi ke Rencana Produksi
     */
    public function rencana()
    {
        return $this->belongsTo(ProductionPlan::class, 'rencana_id');
    }

    /**
     * 🔗 Relasi ke Produk
     */
    public function produk()
    {
        return $this->belongsTo(MasterProduct::class, 'produk_id');
    }

    /**
     * 🔗 Relasi ke Staff Produksi
     */
    public function pekerja()
    {
        return $this->belongsTo(User::class, 'dikerjakan_oleh');
    }

    /**
     * 🔗 Histori perubahan status order
     */
    public function historiStatus()
    {
        return $this->hasMany(ProductionOrderHistory::class, 'order_id');
    }

    /**
     * 🔗 Data reject (produk cacat)
     */
    public function dataReject()
    {
        return $this->hasMany(ProductionReject::class, 'order_id');
    }

    /**
     * 🧠 Accessor label status agar tampil lebih ramah di UI
     */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'menunggu' => 'Menunggu Dikerjakan',
            'dikerjakan' => 'Sedang Dikerjakan',
            'selesai' => 'Selesai',
            default => ucfirst($this->status),
        };
    }

    /**
     * 🔍 Scope untuk memfilter order berdasarkan status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
