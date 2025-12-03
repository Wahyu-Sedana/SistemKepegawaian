<?php

namespace App\Filament\Resources\ListKuotaCutiss\Pages;

use App\Filament\Resources\DataCutiResource;
use App\Filament\Resources\KuotaCutiResource;
use App\Models\PengajuanCuti;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListKuotaCutis extends ListRecords
{
    protected static string $resource = KuotaCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $currentYear = now()->year;
        $user = auth()->user();

        return [
            'tahun_ini' => Tab::make('Tahun Ini')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('tahun', $currentYear))
                ->badge(function () use ($currentYear, $user) {
                    $query = \App\Models\KuotaCuti::where('tahun', $currentYear);
                    if (!$user->hasRole(['super_admin', 'HRD'])) {
                        $query->where('user_id', $user->id);
                    }
                    return $query->count();
                }),

            'tahun_lalu' => Tab::make('Tahun Lalu')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('tahun', $currentYear - 1))
                ->badge(function () use ($currentYear, $user) {
                    $query = \App\Models\KuotaCuti::where('tahun', $currentYear - 1);
                    if (!$user->hasRole(['super_admin', 'HRD'])) {
                        $query->where('user_id', $user->id);
                    }
                    return $query->count();
                }),

            'semua' => Tab::make('Semua Data')
                ->badge(function () use ($user) {
                    $query = \App\Models\KuotaCuti::query();
                    if (!$user->hasRole(['super_admin', 'HRD'])) {
                        $query->where('user_id', $user->id);
                    }
                    return $query->count();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            KuotaCutiResource\Widgets\CutiStatsWidget::class,
        ];
    }
}
