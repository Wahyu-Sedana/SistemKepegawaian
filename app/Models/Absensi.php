<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'latitude_masuk',
        'longitude_masuk',
        'alamat_masuk',
        'latitude_keluar',
        'longitude_keluar',
        'alamat_keluar',
        'foto_masuk',
        'foto_keluar',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_masuk' => 'datetime:H:i:s',
        'jam_keluar' => 'datetime:H:i:s',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hitungJarak($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public static function sudahAbsenMasuk($userId): bool
    {
        return self::where('user_id', $userId)
            ->whereDate('tanggal', today())
            ->whereNotNull('jam_masuk')
            ->exists();
    }


    public static function sudahAbsenKeluar($userId): bool
    {
        return self::where('user_id', $userId)
            ->whereDate('tanggal', today())
            ->whereNotNull('jam_keluar')
            ->exists();
    }

    public static function absensiHariIni($userId)
    {
        return self::where('user_id', $userId)
            ->whereDate('tanggal', today())
            ->first();
    }
}
