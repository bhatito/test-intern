<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'department',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function rencanaDibuat()
    {
        return $this->hasMany(ProductionPlan::class, 'dibuat_oleh');
    }

    public function rencanaDisetujui()
    {
        return $this->hasMany(ProductionPlan::class, 'disetujui_oleh');
    }

    public function orderDikerjakan()
    {
        return $this->hasMany(ProductionOrder::class, 'dikerjakan_oleh');
    }

    public function historiDiubah()
    {
        return $this->hasMany(ProductionOrderHistory::class, 'diubah_oleh');
    }

    public function rejectDicatat()
    {
        return $this->hasMany(ProductionReject::class, 'dicatat_oleh');
    }

    public function laporanDibuat()
    {
        return $this->hasMany(ProductionReport::class, 'dibuat_oleh');
    }
}
