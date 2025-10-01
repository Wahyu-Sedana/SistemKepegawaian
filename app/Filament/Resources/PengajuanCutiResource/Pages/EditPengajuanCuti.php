<?php

namespace App\Filament\Resources\PengajuanCutiResource\Pages;

use App\Filament\Resources\PengajuanCutiResource;
use App\Models\Absensi;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Carbon\Carbon;

class EditPengajuanCuti extends EditRecord
{
    protected static string $resource = PengajuanCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        if ($record->wasChanged('status') && $record->status === 'approved') {
            $tanggalMulai   = Carbon::parse($record->tanggal_mulai);
            $tanggalSelesai = Carbon::parse($record->tanggal_selesai);

            for ($tanggal = $tanggalMulai->copy(); $tanggal->lte($tanggalSelesai); $tanggal->addDay()) {
                Absensi::firstOrCreate(
                    [
                        'user_id' => $record->user_id,
                        'tanggal' => $tanggal->format('Y-m-d'),
                    ],
                    [
                        'status'     => $record->jenis_cuti === 'sakit' ? 'sakit' : 'izin',
                        'keterangan' => $record->alasan ?? $record->jenis_cuti,
                    ]
                );
            }
        }

        if ($record->wasChanged('status') && $record->status === 'rejected') {
            $tanggalMulai   = Carbon::parse($record->tanggal_mulai)->format('Y-m-d');
            $tanggalSelesai = Carbon::parse($record->tanggal_selesai)->format('Y-m-d');

            Absensi::where('user_id', $record->user_id)
                ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                ->whereIn('status', ['sakit', 'izin'])
                ->delete();
        }
    }
}
