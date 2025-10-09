<?php

namespace App\Filament\Widgets;

use App\Models\Absensi;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AbsensiChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Grafik Kehadiran 7 Hari Terakhir';

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected static ?string $maxHeight = '500px';

    protected function getData(): array
    {
        $data = $this->getAbsensiData();

        return [
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $data['hadir'],
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Terlambat',
                    'data' => $data['terlambat'],
                    'backgroundColor' => 'rgba(251, 146, 60, 0.2)',
                    'borderColor' => 'rgb(251, 146, 60)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Izin/Sakit',
                    'data' => $data['izin'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Alpha',
                    'data' => $data['alpha'],
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'boxWidth' => 12,
                        'padding' => 10,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'font' => [
                            'size' => 10,
                        ],
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'font' => [
                            'size' => 10,
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function getAbsensiData(): array
    {
        $labels = [];
        $hadir = [];
        $terlambat = [];
        $izin = [];
        $alpha = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->translatedFormat('D, d M');

            // Hadir
            $hadir[] = Absensi::whereDate('tanggal', $date)
                ->where('status', 'hadir')
                ->distinct('user_id')
                ->count('user_id');

            // Terlambat
            $terlambat[] = Absensi::whereDate('tanggal', $date)
                ->where('status', 'terlambat')
                ->distinct('user_id')
                ->count('user_id');

            // Izin/Sakit
            $izin[] = Absensi::whereDate('tanggal', $date)
                ->whereIn('status', ['izin', 'sakit'])
                ->distinct('user_id')
                ->count('user_id');

            // Alpha
            $alpha[] = Absensi::whereDate('tanggal', $date)
                ->where('status', 'alpha')
                ->distinct('user_id')
                ->count('user_id');
        }

        return compact('labels', 'hadir', 'terlambat', 'izin', 'alpha');
    }

    public static function canView(): bool
    {
        // Hanya tampil untuk Admin dan HRD
        return auth()->user()->hasRole(['super_admin', 'HRD']);
    }
}
