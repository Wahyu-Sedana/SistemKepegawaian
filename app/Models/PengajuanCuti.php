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

    protected static function booted()
    {
        static::created(function ($pengajuanCuti) {
            if ($pengajuanCuti->status === 'approved') {
                $pengajuanCuti->buatAbsensiCuti();
            }
        });

        static::updated(function ($pengajuanCuti) {
            if ($pengajuanCuti->isDirty('status') && $pengajuanCuti->status === 'approved') {
                $pengajuanCuti->buatAbsensiCuti();
            }
        });
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
