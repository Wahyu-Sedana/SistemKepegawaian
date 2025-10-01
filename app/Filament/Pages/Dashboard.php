<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AbsensiGPSWidget;
use App\Filament\Widgets\AbsensiStatsWidget;
use Filament\Pages\Page;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            AbsensiGPSWidget::class,
            AbsensiStatsWidget::class,
        ];
    }
}
