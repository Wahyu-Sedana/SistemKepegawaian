<?php

namespace App\Filament\Resources\KuotaCutiResource\Pages;

use App\Filament\Resources\KuotaCutiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKuotaCuti extends EditRecord
{
    protected static string $resource = KuotaCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
