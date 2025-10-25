<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProductionReject extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'production_rejects';
    protected $fillable = ['order_id', 'jenis_cacat', 'jumlah', 'dicatat_oleh'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function order()
    {
        return $this->belongsTo(ProductionOrder::class, 'order_id');
    }

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
