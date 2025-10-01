<?php

namespace App\Filament\Resources\PengajuanCutiResource\Pages;

use App\Filament\Resources\PengajuanCutiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePengajuanCuti extends CreateRecord
{
    protected static string $resource = PengajuanCutiResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!auth()->user()->hasRole(['super_admin', 'HRD'])) {
            $data['user_id'] = auth()->id();
            $data['status'] = 'pending';
        }

        return $data;
    }
}
