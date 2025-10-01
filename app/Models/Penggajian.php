<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penggajian extends Model
{
    use HasFactory;
    protected $table = 'penggajian';

    protected $fillable = [
        'user_id',
        'tanggal_gaji',
        'periode',
        'gaji_pokok',
        'tunjangan',
        'potongan',
        'gaji_bersih',
        'status',
    ];

    protected $casts = [
        'tanggal_gaji' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
