<?php

namespace App\Filament\Widgets;

use App\Models\Absensi;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AbsensiStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = auth()->user();

        // Admin/HRD: Stats global
        if ($user->hasRole(['super_admin', 'HRD'])) {
            return $this->getAdminStats();
        }

        // Staff: Stats personal
        return $this->getStaffStats();
    }

    protected function getAdminStats(): array
    {
        $totalKaryawan = User::role('Staff')->count();

        $absenHariIni = Absensi::whereDate('tanggal', today())
            ->distinct('user_id')
            ->count('user_id');

        $bulanIni = now()->startOfMonth();
        $akhirBulan = now()->endOfMonth();

        $izinSakitBulanIni = Absensi::whereBetween('tanggal', [$bulanIni, $akhirBulan])
            ->whereIn('status', ['izin', 'sakit'])
            ->count();

        $terlambatBulanIni = Absensi::whereBetween('tanggal', [$bulanIni, $akhirBulan])
            ->where('status', 'terlambat')
            ->count();

        // Chart data untuk kehadiran 7 hari terakhir
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = Absensi::whereDate('tanggal', $date)
                ->distinct('user_id')
                ->count('user_id');
            $chartData[] = $count;
        }

        return [
            Stat::make('Total Karyawan', $totalKaryawan . ' orang')
                ->description('Staff aktif')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->chart([5, 6, 7, 8, 7, 9, 10]), // Dummy trend

            Stat::make('Absen Hari Ini', $absenHariIni . ' / ' . $totalKaryawan)
                ->description(round(($totalKaryawan > 0 ? $absenHariIni / $totalKaryawan : 0) * 100) . '% kehadiran')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($absenHariIni >= $totalKaryawan * 0.8 ? 'success' : 'warning')
                ->chart($chartData),

            Stat::make('Izin/Sakit Bulan Ini', $izinSakitBulanIni . ' kali')
                ->description('Total tidak masuk bulan ' . now()->format('F'))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Terlambat Bulan Ini', $terlambatBulanIni . ' kali')
                ->description('Keterlambatan bulan ini')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }

    protected function getStaffStats(): array
    {
        $userId = auth()->id();
        $bulanIni = now()->startOfMonth();
        $akhirBulan = now()->endOfMonth();

        $hadirBulanIni = Absensi::where('user_id', $userId)
            ->whereBetween('tanggal', [$bulanIni, $akhirBulan])
            ->whereIn('status', ['hadir', 'terlambat'])
            ->count();

        $terlambatBulanIni = Absensi::where('user_id', $userId)
            ->whereBetween('tanggal', [$bulanIni, $akhirBulan])
            ->where('status', 'terlambat')
            ->count();

        $izinSakitBulanIni = Absensi::where('user_id', $userId)
            ->whereBetween('tanggal', [$bulanIni, $akhirBulan])
            ->whereIn('status', ['izin', 'sakit'])
            ->count();

        // Status hari ini
        $absensiHariIni = Absensi::where('user_id', $userId)
            ->whereDate('tanggal', today())
            ->first();

        $statusHariIni = 'Belum Absen';
        $colorHariIni = 'gray';
        $iconHariIni = 'heroicon-m-x-circle';

        if ($absensiHariIni) {
            if ($absensiHariIni->jam_keluar) {
                $statusHariIni = '✅ Check-out';
                $colorHariIni = 'success';
                $iconHariIni = 'heroicon-m-check-circle';
            } elseif ($absensiHariIni->jam_masuk) {
                $statusHariIni = '🟢 Check-in';
                $colorHariIni = 'warning';
                $iconHariIni = 'heroicon-m-arrow-right-on-rectangle';
            }
        }

        // Chart data kehadiran 7 hari terakhir
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = Absensi::where('user_id', $userId)
                ->whereDate('tanggal', $date)
                ->whereIn('status', ['hadir', 'terlambat'])
                ->exists() ? 1 : 0;
            $chartData[] = $count;
        }

        return [
            Stat::make('Kehadiran Bulan Ini', $hadirBulanIni . ' hari')
                ->description('Total masuk kerja')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success')
                ->chart($chartData),

            Stat::make('Terlambat Bulan Ini', $terlambatBulanIni . ' kali')
                ->description('Datang terlambat')
                ->descriptionIcon('heroicon-m-clock')
                ->color($terlambatBulanIni > 5 ? 'danger' : 'warning'),

            Stat::make('Izin/Sakit Bulan Ini', $izinSakitBulanIni . ' hari')
                ->description('Total tidak masuk')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Status Hari Ini', $statusHariIni)
                ->description(now()->translatedFormat('l, d F Y'))
                ->descriptionIcon($iconHariIni)
                ->color($colorHariIni),
        ];
    }
}
