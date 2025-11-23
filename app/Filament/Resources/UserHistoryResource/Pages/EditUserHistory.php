<?php

namespace App\Filament\Resources\UserHistoryResource\Pages;

use App\Filament\Resources\UserHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserHistory extends EditRecord
{
    protected static string $resource = UserHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
