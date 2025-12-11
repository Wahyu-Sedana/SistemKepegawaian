<?php

namespace App\Filament\Resources\PengajuanCutiResource\Pages;

use App\Filament\Resources\PengajuanCutiResource;
use App\Models\PengajuanCuti;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class CreatePengajuanCuti extends CreateRecord
{
    protected static string $resource = PengajuanCutiResource::class;

    protected function beforeCreate(): void
    {
        $userId = auth()->id();
        $tahun = Carbon::parse($this->data['tanggal_mulai'])->year;
        $bulan = Carbon::parse($this->data['tanggal_mulai'])->month;

        $sisaKuota = PengajuanCuti::getSisaKuotaCuti($userId, $tahun);
        $jumlahPengajuanBulanIni = PengajuanCuti::getJumlahPengajuanBulanIni($userId, $bulan, $tahun);

        if ($this->data['jumlah_hari'] > $sisaKuota) {
            Notification::make()
                ->danger()
                ->title('Pengajuan Cuti Gagal!')
                ->body("Sisa kuota cuti Anda hanya {$sisaKuota} hari. Anda mengajukan {$this->data['jumlah_hari']} hari.")
                ->persistent()
                ->send();

            $this->halt();
        }


        if ($jumlahPengajuanBulanIni >= PengajuanCuti::MAX_PENGAJUAN_PER_BULAN) {
            Notification::make()
                ->danger()
                ->title('Pengajuan Cuti Gagal!')
                ->body("Maksimal pengajuan cuti per bulan adalah " . PengajuanCuti::MAX_PENGAJUAN_PER_BULAN . " kali. Anda sudah mengajukan {$jumlahPengajuanBulanIni} kali bulan ini.")
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!auth()->user()->hasRole(['super_admin', 'HRD'])) {
            $data['user_id'] = auth()->id();
            $data['status'] = 'pending';
        }

        return $data;
    }
}
