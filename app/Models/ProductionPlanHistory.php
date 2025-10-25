<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionPlanHistory extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'production_plan_histories';

    protected $fillable = [
        'rencana_id',
        'user_id',
        'aksi',
        'status_sebelum',
        'status_baru',
        'keterangan',
        'waktu_aksi'
    ];

    protected $casts = [
        'waktu_aksi' => 'datetime'
    ];

    /**
     * Relasi ke rencana produksi
     */
    public function rencana(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class, 'rencana_id');
    }

    /**
     * Relasi ke user yang melakukan aksi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
