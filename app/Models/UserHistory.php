<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserHistory extends Model
{
    use HasFactory;

    protected $table = 'user_history';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'user_data',
        'deleted_at',
        'deleted_by',
        'deletion_reason',
    ];

    protected $casts = [
        'user_data' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relasi ke user yang menghapus
     */
    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Create history from user before deletion
     * Generate summary data absensi & penggajian per bulan
     */
    public static function createFromUser(User $user, ?int $deletedBy = null, ?string $reason = null): self
    {
        // Generate summary data per bulan
        $summaryData = self::generateMonthlySummary($user->id);

        return self::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'user_data' => $summaryData,
            'deleted_at' => now(),
            'deleted_by' => $deletedBy,
            'deletion_reason' => $reason,
        ]);
    }

    /**
     * Generate summary data per bulan untuk user
     */
    private static function generateMonthlySummary(int $userId): array
    {
        // Ambil semua absensi
        $absensis = Absensi::where('user_id', $userId)
            ->orderBy('tanggal')
            ->get();

        // Ambil semua penggajian
        $penggajians = Penggajian::where('user_id', $userId)
            ->orderBy('periode')
            ->get();

        if ($absensis->isEmpty() && $penggajians->isEmpty()) {
            return [
                'total_bulan_bekerja' => 0,
                'periode_pertama' => null,
                'periode_terakhir' => null,
                'monthly_data' => [],
                'ringkasan_keseluruhan' => [
                    'total_hadir' => 0,
                    'total_terlambat' => 0,
                    'total_cuti' => 0,
                    'total_alpha' => 0,
                    'total_gaji_diterima' => 0
                ]
            ];
        }

        // Group absensi per bulan
        $absensiPerBulan = $absensis->groupBy(function ($item) {
            return Carbon::parse($item->tanggal)->format('Y-m');
        });

        // Build summary per bulan
        $monthlySummary = [];
        $jamMasukNormal = config('payroll.jam_masuk_normal', '08:00:00');
        $toleransi = config('payroll.toleransi_keterlambatan', 15);

        foreach ($absensiPerBulan as $periode => $dataAbsensi) {
            $totalHadir = 0;
            $totalTerlambat = 0;
            $totalCuti = 0;
            $totalAlpha = 0;
            $totalJamLembur = 0;
            $detailAbsensi = [];

            // Hitung per tanggal dalam bulan
            $startDate = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $periode)->endOfMonth();
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                // Skip Minggu
                if ($currentDate->isSunday()) {
                    $currentDate->addDay();
                    continue;
                }

                // Cari absensi untuk tanggal ini
                $absensi = $dataAbsensi->first(function ($item) use ($currentDate) {
                    return Carbon::parse($item->tanggal)->isSameDay($currentDate);
                });

                if ($absensi) {
                    // Cek status cuti
                    if ($absensi->status == 'cuti') {
                        $totalCuti++;
                        $detailAbsensi[] = [
                            'tanggal' => $currentDate->format('Y-m-d'),
                            'status' => 'cuti',
                            'keterangan' => $absensi->keterangan
                        ];
                    }
                    // Cek jika ada jam masuk (hadir)
                    elseif ($absensi->jam_masuk) {
                        $totalHadir++;

                        // Cek keterlambatan
                        $jamMasuk = Carbon::parse($absensi->jam_masuk);
                        $batasWaktu = Carbon::parse($currentDate->format('Y-m-d') . ' ' . $jamMasukNormal)
                            ->addMinutes($toleransi);

                        $isTerlambat = $jamMasuk->gt($batasWaktu);
                        if ($isTerlambat) {
                            $totalTerlambat++;
                        }

                        // Hitung jam lembur
                        if (isset($absensi->jam_lembur) && $absensi->jam_lembur > 0) {
                            $totalJamLembur += $absensi->jam_lembur;
                        }

                        $detailAbsensi[] = [
                            'tanggal' => $currentDate->format('Y-m-d'),
                            'status' => 'hadir',
                            'jam_masuk' => $jamMasuk->format('H:i:s'),
                            'jam_keluar' => $absensi->jam_keluar ? Carbon::parse($absensi->jam_keluar)->format('H:i:s') : null,
                            'terlambat' => $isTerlambat,
                            'jam_lembur' => $absensi->jam_lembur ?? 0
                        ];
                    } else {
                        // Ada data absensi tapi tidak ada jam masuk = alpha
                        $totalAlpha++;
                        $detailAbsensi[] = [
                            'tanggal' => $currentDate->format('Y-m-d'),
                            'status' => 'alpha'
                        ];
                    }
                } else {
                    // Tidak ada data absensi = alpha
                    $totalAlpha++;
                    $detailAbsensi[] = [
                        'tanggal' => $currentDate->format('Y-m-d'),
                        'status' => 'alpha'
                    ];
                }

                $currentDate->addDay();
            }

            // Get data penggajian untuk periode ini
            $gaji = $penggajians->firstWhere('periode', $periode);

            $monthlySummary[$periode] = [
                'periode' => $periode,
                'periode_text' => Carbon::createFromFormat('Y-m', $periode)->isoFormat('MMMM YYYY'),
                'absensi' => [
                    'total_hadir' => $totalHadir,
                    'total_terlambat' => $totalTerlambat,
                    'total_cuti' => $totalCuti,
                    'total_alpha' => $totalAlpha,
                    'total_jam_lembur' => $totalJamLembur,
                    'detail' => $detailAbsensi
                ],
                'penggajian' => $gaji ? [
                    'gaji_pokok' => (float) $gaji->gaji_pokok,
                    'gaji_harian' => (float) $gaji->gaji_harian,
                    'tunjangan' => (float) $gaji->tunjangan,
                    'total_lembur' => (float) $gaji->total_lembur,
                    'potongan' => (float) $gaji->potongan,
                    'gaji_bersih' => (float) $gaji->gaji_bersih,
                    'status' => $gaji->status,
                    'tanggal_gaji' => $gaji->tanggal_gaji?->format('Y-m-d'),
                    'detail_potongan' => $gaji->detail_potongan
                ] : null
            ];
        }

        // Hitung ringkasan keseluruhan
        $totalHadirAll = 0;
        $totalTerlambatAll = 0;
        $totalCutiAll = 0;
        $totalAlphaAll = 0;

        foreach ($monthlySummary as $data) {
            $totalHadirAll += $data['absensi']['total_hadir'];
            $totalTerlambatAll += $data['absensi']['total_terlambat'];
            $totalCutiAll += $data['absensi']['total_cuti'];
            $totalAlphaAll += $data['absensi']['total_alpha'];
        }

        return [
            'total_bulan_bekerja' => count($monthlySummary),
            'periode_pertama' => $absensis->first()?->tanggal?->format('Y-m-d'),
            'periode_terakhir' => $absensis->last()?->tanggal?->format('Y-m-d'),
            'monthly_data' => $monthlySummary,
            'ringkasan_keseluruhan' => [
                'total_hadir' => $totalHadirAll,
                'total_terlambat' => $totalTerlambatAll,
                'total_cuti' => $totalCutiAll,
                'total_alpha' => $totalAlphaAll,
                'total_gaji_diterima' => (float) $penggajians->sum('gaji_bersih')
            ]
        ];
    }

    /**
     * Get user info by user_id (dari user aktif atau history)
     */
    public static function getUserInfo(int $userId): ?array
    {
        // Cek user aktif
        $user = User::find($userId);
        if ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'status' => 'active',
            ];
        }

        // Cek di history
        $history = self::where('user_id', $userId)->latest('deleted_at')->first();
        if ($history) {
            return [
                'id' => $history->user_id,
                'name' => $history->name,
                'email' => $history->email,
                'phone' => $history->phone,
                'status' => 'deleted',
                'deleted_at' => $history->deleted_at,
            ];
        }

        return null;
    }

    /**
     * Get monthly summary from history
     */
    public function getMonthlySummary(?string $periode = null): ?array
    {
        if (!$this->user_data) {
            return null;
        }

        $data = $this->user_data;

        if ($periode) {
            // Return specific month
            return $data['monthly_data'][$periode] ?? null;
        }

        // Return all data
        return $data;
    }

    /**
     * Get ringkasan keseluruhan
     */
    public function getRingkasanAttribute(): ?array
    {
        return $this->user_data['ringkasan_keseluruhan'] ?? null;
    }
}
