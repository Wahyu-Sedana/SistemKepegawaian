<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PengajuanCuti extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'jenis_cuti',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah_hari',
        'alasan',
        'status',
        'catatan_hrd',
        'disetujui_oleh_id',
        'bukti_surat'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    const KUOTA_CUTI_TAHUNAN = 12;
    const MAX_PENGAJUAN_PER_BULAN = 3;

    protected static function booted()
    {
        static::creating(function ($pengajuanCuti) {
            $pengajuanCuti->validateKuotaCuti();
        });

        static::created(function ($pengajuanCuti) {
            if ($pengajuanCuti->status === 'approved') {
                $pengajuanCuti->kurangiKuotaCuti();
                $pengajuanCuti->buatAbsensiCuti();
            }
        });

        static::updated(function ($pengajuanCuti) {
            if ($pengajuanCuti->isDirty('status') && $pengajuanCuti->status === 'approved') {
                $pengajuanCuti->kurangiKuotaCuti();
                $pengajuanCuti->buatAbsensiCuti();
            }

            if (
                $pengajuanCuti->isDirty('status') &&
                in_array($pengajuanCuti->status, ['rejected', 'cancelled']) &&
                $pengajuanCuti->getOriginal('status') === 'approved'
            ) {
                $pengajuanCuti->kembalikanKuotaCuti();
            }
        });
    }

    public function validateKuotaCuti()
    {
        $tahun = Carbon::parse($this->tanggal_mulai)->year;
        $bulan = Carbon::parse($this->tanggal_mulai)->month;


        $jumlahPengajuanBulanIni = self::where('user_id', $this->user_id)
            ->whereYear('tanggal_mulai', $tahun)
            ->whereMonth('tanggal_mulai', $bulan)
            ->count();

        if ($jumlahPengajuanBulanIni >= self::MAX_PENGAJUAN_PER_BULAN) {
            throw new \Exception("Maksimal pengajuan cuti per bulan adalah " . self::MAX_PENGAJUAN_PER_BULAN . " kali");
        }

        $sisaKuota = $this->getSisaKuotaCuti($this->user_id, $tahun);

        if ($this->jumlah_hari > $sisaKuota) {
            throw new \Exception("Sisa kuota cuti Anda hanya {$sisaKuota} hari. Anda mengajukan {$this->jumlah_hari} hari.");
        }
    }


    public function kurangiKuotaCuti()
    {
        $tahun = Carbon::parse($this->tanggal_mulai)->year;

        $kuotaCuti = KuotaCuti::firstOrCreate(
            [
                'user_id' => $this->user_id,
                'tahun' => $tahun
            ],
            [
                'kuota_awal' => self::KUOTA_CUTI_TAHUNAN,
                'kuota_terpakai' => 0,
                'kuota_tersisa' => self::KUOTA_CUTI_TAHUNAN
            ]
        );

        $kuotaCuti->kuota_terpakai += $this->jumlah_hari;
        $kuotaCuti->kuota_tersisa = $kuotaCuti->kuota_awal - $kuotaCuti->kuota_terpakai;
        $kuotaCuti->save();
    }


    public function kembalikanKuotaCuti()
    {
        $tahun = Carbon::parse($this->tanggal_mulai)->year;

        $kuotaCuti = KuotaCuti::where('user_id', $this->user_id)
            ->where('tahun', $tahun)
            ->first();

        if ($kuotaCuti) {
            $kuotaCuti->kuota_terpakai -= $this->jumlah_hari;
            $kuotaCuti->kuota_tersisa = $kuotaCuti->kuota_awal - $kuotaCuti->kuota_terpakai;
            $kuotaCuti->save();
        }
    }

    public static function getSisaKuotaCuti($userId, $tahun = null)
    {
        $tahun = $tahun ?? Carbon::now()->year;

        $kuotaCuti = KuotaCuti::where('user_id', $userId)
            ->where('tahun', $tahun)
            ->first();

        if (!$kuotaCuti) {
            return self::KUOTA_CUTI_TAHUNAN;
        }

        return $kuotaCuti->kuota_tersisa;
    }

    public static function getJumlahPengajuanBulanIni($userId, $bulan = null, $tahun = null)
    {
        $bulan = $bulan ?? Carbon::now()->month;
        $tahun = $tahun ?? Carbon::now()->year;

        return self::where('user_id', $userId)
            ->whereYear('tanggal_mulai', $tahun)
            ->whereMonth('tanggal_mulai', $bulan)
            ->count();
    }

    public function buatAbsensiCuti()
    {
        $tanggalMulai = Carbon::parse($this->tanggal_mulai);
        $tanggalSelesai = Carbon::parse($this->tanggal_selesai);

        for ($tanggal = $tanggalMulai->copy(); $tanggal->lte($tanggalSelesai); $tanggal->addDay()) {
            if ($tanggal->isSunday()) {
                continue;
            }

            $existingAbsensi = Absensi::where('user_id', $this->user_id)
                ->whereDate('tanggal', $tanggal)
                ->first();

            if (!$existingAbsensi) {
                Absensi::create([
                    'user_id' => $this->user_id,
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'status' => 'cuti',
                    'keterangan' => "Cuti: {$this->jenis_cuti} - {$this->alasan}",
                ]);
            } else {
                $existingAbsensi->update([
                    'status' => 'cuti',
                    'keterangan' => "Cuti: {$this->jenis_cuti} - {$this->alasan}",
                ]);
            }
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function disetujuiOleh()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh_id');
    }
}
