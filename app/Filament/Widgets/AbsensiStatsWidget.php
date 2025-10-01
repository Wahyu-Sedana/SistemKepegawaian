<?php

namespace App\Filament\Widgets;

use App\Models\Absensi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AbsensiStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = auth()->user();
        $bulanIni = now()->startOfMonth();
        $bulanDepan = now()->endOfMonth();

        // =========================
        // Rekap Bulanan
        // =========================
        $hadirBulanIni = Absensi::query()
            ->when($user->hasRole('Staff'), fn($q) => $q->where('user_id', $user->id))
            ->whereBetween('tanggal', [$bulanIni, $bulanDepan])
            ->whereIn('status', ['hadir', 'terlambat'])
            ->count();

        $terlambatBulanIni = Absensi::query()
            ->when($user->hasRole('Staff'), fn($q) => $q->where('user_id', $user->id))
            ->whereBetween('tanggal', [$bulanIni, $bulanDepan])
            ->where('status', 'terlambat')
            ->count();

        $izinBulanIni = Absensi::query()
            ->when($user->hasRole('Staff'), fn($q) => $q->where('user_id', $user->id))
            ->whereBetween('tanggal', [$bulanIni, $bulanDepan])
            ->whereIn('status', ['izin', 'sakit'])
            ->count();

        // =========================
        // Status Hari Ini
        // =========================
        $statusHariIni = 'Belum Ada Data';
        $colorHariIni = 'gray';

        if ($user->hasRole('Staff')) {
            $absensiHariIni = Absensi::where('user_id', $user->id)
                ->whereDate('tanggal', today())
                ->first();

            if ($absensiHariIni) {
                if ($absensiHariIni->jam_keluar) {
                    $statusHariIni = '✅ Sudah Check-out';
                    $colorHariIni = 'success';
                } elseif ($absensiHariIni->jam_masuk) {
                    $statusHariIni = '🟡 Sudah Check-in';
                    $colorHariIni = 'warning';
                }
            }
        } elseif ($user->hasRole(['super_admin', 'HRD'])) {
            $totalStaffAbsen = Absensi::whereDate('tanggal', today())->count();
            $statusHariIni = $totalStaffAbsen . ' Staff Sudah Absen';
            $colorHariIni = $totalStaffAbsen > 0 ? 'success' : 'gray';
        }

        return [
            Stat::make('Kehadiran Bulan Ini', $hadirBulanIni . ' hari')
                ->description('Total hadir & terlambat')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Terlambat Bulan Ini', $terlambatBulanIni . ' hari')
                ->description('Datang terlambat')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Izin/Sakit Bulan Ini', $izinBulanIni . ' hari')
                ->description('Total tidak masuk')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Status Hari Ini', $statusHariIni)
                ->description(now()->format('d F Y'))
                ->descriptionIcon('heroicon-m-finger-print')
                ->color($colorHariIni),
        ];
    }
}
