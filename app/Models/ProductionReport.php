<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProductionReport extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'production_reports';
    protected $fillable = ['nomor_laporan', 'periode_awal', 'periode_akhir', 'dibuat_oleh', 'catatan'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
