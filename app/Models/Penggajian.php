<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Penggajian extends Model
{
    use HasFactory;

    protected $table = 'penggajian';

    protected $fillable = [
        'user_id',
        'periode',
        'tanggal_gaji',
        'gaji_pokok',
        'tunjangan',
        'potongan',
        'gaji_bersih',
        'status',
        'detail_potongan',
    ];

    protected $casts = [
        'tanggal_gaji' => 'date',
        'detail_potongan' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public static function hitungPotonganAbsensi($userId, $periode): array
    {
        $date = Carbon::createFromFormat('Y-m', $periode);
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        $jamMasukNormal = config('payroll.jam_masuk_normal', '08:00:00');
        $toleransi = config('payroll.toleransi_keterlambatan', 15);
        $potonganTerlambat = config('payroll.potongan_per_keterlambatan', 50000);
        $potonganAbsen = config('payroll.potongan_per_absen', 100000);

        $totalPotongan = 0;
        $details = [
            'periode_absensi' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
            'keterlambatan' => [],
            'tidak_hadir' => [],
            'summary' => []
        ];

        $currentDate = $startDate->copy();
        $jumlahHariKerja = 0;

        while ($currentDate <= $endDate) {
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                continue;
            }

            $jumlahHariKerja++;

            $absensi = \App\Models\Absensi::where('user_id', $userId)
                ->whereDate('tanggal', $currentDate)
                ->first();

            if (!$absensi) {
                $totalPotongan += $potonganAbsen;
                $details['tidak_hadir'][] = [
                    'tanggal' => $currentDate->format('d M Y (l)'),
                    'tanggal_raw' => $currentDate->format('Y-m-d'),
                    'potongan' => $potonganAbsen
                ];
            } elseif ($absensi->jam_masuk) {
                $jamMasuk = Carbon::parse($absensi->jam_masuk);
                $batasWaktu = Carbon::parse($currentDate->format('Y-m-d') . ' ' . $jamMasukNormal)
                    ->addMinutes($toleransi);

                if ($jamMasuk->gt($batasWaktu)) {
                    $menitTerlambat = $jamMasuk->diffInMinutes(
                        Carbon::parse($currentDate->format('Y-m-d') . ' ' . $jamMasukNormal)
                    );
                    $totalPotongan += $potonganTerlambat;
                    $details['keterlambatan'][] = [
                        'tanggal' => $currentDate->format('d M Y (l)'),
                        'tanggal_raw' => $currentDate->format('Y-m-d'),
                        'jam_masuk' => $jamMasuk->format('H:i:s'),
                        'terlambat' => $menitTerlambat . ' menit',
                        'terlambat_menit' => $menitTerlambat,
                        'potongan' => $potonganTerlambat
                    ];
                }
            }

            $currentDate->addDay();
        }

        // Summary
        $details['summary'] = [
            'jumlah_hari_kerja' => $jumlahHariKerja,
            'jumlah_hadir' => $jumlahHariKerja - count($details['tidak_hadir']),
            'jumlah_keterlambatan' => count($details['keterlambatan']),
            'jumlah_tidak_hadir' => count($details['tidak_hadir']),
            'total_potongan' => $totalPotongan
        ];

        return [
            'total' => $totalPotongan,
            'details' => $details
        ];
    }
}
