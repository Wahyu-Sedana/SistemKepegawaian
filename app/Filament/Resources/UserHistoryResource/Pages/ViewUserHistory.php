<?php

namespace App\Filament\Resources\UserHistoryResource\Pages;

use App\Filament\Resources\UserHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUserHistory extends ViewRecord
{
    protected static string $resource = UserHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
