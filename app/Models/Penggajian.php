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
        'gaji_harian',
        'tunjangan',
        'total_jam_lembur',
        'total_lembur',
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

    public static function hitungPotonganAbsensi($userId, $periode, $gajiHarian = 0): array
    {
        $date = Carbon::createFromFormat('Y-m', $periode);
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        $jamMasukNormal = config('payroll.jam_masuk_normal', '08:00:00');
        $toleransi = config('payroll.toleransi_keterlambatan', 15);
        $potonganTerlambat = config('payroll.potongan_per_keterlambatan', 50000);

        $totalPotonganKeterlambatan = 0;
        $details = [
            'periode_absensi' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
            'keterlambatan' => [],
            'tidak_hadir' => [],
            'hadir' => [],
            'summary' => []
        ];

        $currentDate = $startDate->copy();
        $jumlahHariKerja = 0;
        $jumlahHadir = 0;

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
                $details['tidak_hadir'][] = [
                    'tanggal' => $currentDate->format('d M Y (l)'),
                    'tanggal_raw' => $currentDate->format('Y-m-d'),
                ];
            } elseif ($absensi->jam_masuk) {
                $jumlahHadir++;
                $details['hadir'][] = [
                    'tanggal' => $currentDate->format('d M Y (l)'),
                    'tanggal_raw' => $currentDate->format('Y-m-d'),
                    'jam_masuk' => Carbon::parse($absensi->jam_masuk)->format('H:i:s'),
                ];

                $jamMasuk = Carbon::parse($absensi->jam_masuk);
                $batasWaktu = Carbon::parse($currentDate->format('Y-m-d') . ' ' . $jamMasukNormal)
                    ->addMinutes($toleransi);

                if ($jamMasuk->gt($batasWaktu)) {
                    $menitTerlambat = $jamMasuk->diffInMinutes(
                        Carbon::parse($currentDate->format('Y-m-d') . ' ' . $jamMasukNormal)
                    );
                    $totalPotonganKeterlambatan += $potonganTerlambat;

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

        $gajiDapatDariKehadiran = $gajiHarian * $jumlahHadir;


        $totalPotongan = $totalPotonganKeterlambatan;

        $details['summary'] = [
            'jumlah_hari_kerja' => $jumlahHariKerja,
            'jumlah_hadir' => $jumlahHadir,
            'jumlah_keterlambatan' => count($details['keterlambatan']),
            'jumlah_tidak_hadir' => count($details['tidak_hadir']),
            'gaji_harian' => $gajiHarian,
            'gaji_dari_kehadiran' => $gajiDapatDariKehadiran,
            'potongan_keterlambatan' => $totalPotonganKeterlambatan,
            'total_potongan' => $totalPotongan
        ];

        return [
            'total' => $totalPotongan,
            'gaji_dari_kehadiran' => $gajiDapatDariKehadiran,
            'details' => $details
        ];
    }

    public static function hitungLembur($userId, $periode): array
    {
        $date = Carbon::createFromFormat('Y-m', $periode);
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        $tarifPerJam = 15000;
        $totalJamLembur = 0;
        $details = [
            'periode_lembur' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
            'data_lembur' => [],
            'summary' => []
        ];

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                continue;
            }

            $absensi = \App\Models\Absensi::where('user_id', $userId)
                ->whereDate('tanggal', $currentDate)
                ->first();

            if ($absensi && $absensi->jam_lembur > 0) {
                $totalJamLembur += $absensi->jam_lembur;

                $details['data_lembur'][] = [
                    'tanggal' => $currentDate->format('d M Y (l)'),
                    'tanggal_raw' => $currentDate->format('Y-m-d'),
                    'jam_lembur' => $absensi->jam_lembur,
                    'nominal' => $absensi->jam_lembur * $tarifPerJam
                ];
            }

            $currentDate->addDay();
        }

        $totalNominal = $totalJamLembur * $tarifPerJam;

        $details['summary'] = [
            'total_jam_lembur' => $totalJamLembur,
            'tarif_per_jam' => $tarifPerJam,
            'total_nominal' => $totalNominal
        ];

        return [
            'total_jam' => $totalJamLembur,
            'total_nominal' => $totalNominal,
            'details' => $details
        ];
    }
}
