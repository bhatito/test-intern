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
        'target_jumlah' => 'integer',
        'jumlah_aktual' => 'integer',
        'jumlah_reject' => 'integer',
    ];

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
     * 🔗 Relasi ke Staff Produksi - PASTIKAN RELASI INI ADA
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
        $statusLabels = [
            'menunggu' => 'Menunggu',
            'dalam_proses' => 'Dalam Proses',
            'selesai' => 'Selesai'
            // Hapus ditutup karena tidak ada
        ];

        return $statusLabels[$this->status] ?? $this->status;
    }

    /**
     * 🧠 Accessor untuk progress persentase
     */
    public function getProgressPercentageAttribute()
    {
        if (!$this->jumlah_aktual || !$this->target_jumlah) {
            return 0;
        }

        return min(round(($this->jumlah_aktual / $this->target_jumlah) * 100), 100);
    }

    public static function booted()
    {
        static::creating(function ($order) {
            if (!$order->nomor_order) {
                $latest = self::latest('created_at')->first();
                $no = $latest ? intval(substr($latest->nomor_order, -4)) + 1 : 1;
                $order->nomor_order = 'ORD-' . str_pad($no, 4, '0', STR_PAD_LEFT);
            }
        });

        static::updating(function ($model) {
            // Auto-set selesai_pada ketika status jadi selesai
            if ($model->isDirty('status') && $model->status === 'selesai') {
                $model->selesai_pada = now();
            }

            // Auto-set mulai_pada ketika status jadi dalam_proses
            if ($model->isDirty('status') && $model->status === 'dalam_proses' && !$model->mulai_pada) {
                $model->mulai_pada = now();
            }
        });
    }

    /**
     * 🔍 Scope untuk memfilter order berdasarkan status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 🔍 Scope untuk mencari berdasarkan nomor order atau nama produk
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nomor_order', 'like', "%{$search}%")
                ->orWhereHas('produk', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%");
                });
        });
    }

    /**
     * 🔍 Scope untuk filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate = null)
    {
        $endDate = $endDate ?: $startDate;

        return $query->whereBetween('created_at', [
            $startDate . ' 00:00:00',
            $endDate . ' 23:59:59'
        ]);
    }
}
