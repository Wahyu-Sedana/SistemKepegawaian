<?php

namespace App\Filament\Widgets;

use App\Models\Absensi;
use Filament\Widgets\Widget;

class AbsensiGPSWidget extends Widget
{
    protected static string $view = 'filament.widgets.absensi-gps-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = auth()->user();
        $absensiHariIni = Absensi::absensiHariIni($user->id);

        return [
            'sudahCheckIn' => $absensiHariIni && $absensiHariIni->jam_masuk,
            'sudahCheckOut' => $absensiHariIni && $absensiHariIni->jam_keluar,
            'absensi' => $absensiHariIni,
        ];
    }
    public static function canView(): bool
    {
        return auth()->user()->hasRole(['Staff', 'HRD']);
    }
}
