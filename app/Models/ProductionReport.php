<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProductionReport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'nomor_laporan',
        'periode_awal',
        'periode_akhir',
        'dibuat_oleh',
        'catatan',
    ];

    protected $casts = [
        'periode_awal' => 'date',
        'periode_akhir' => 'date',
    ];

    /**
     * Relasi ke user yang membuat laporan
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
