<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KuotaCuti extends Model
{
    use HasFactory;

    protected $table = 'kuota_cuti';

    protected $fillable = [
        'user_id',
        'tahun',
        'kuota_awal',
        'kuota_terpakai',
        'kuota_tersisa'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
