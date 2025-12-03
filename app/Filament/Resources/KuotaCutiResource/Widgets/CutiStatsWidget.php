<?php

namespace App\Filament\Resources\KuotaCutiResource\Widgets;

use App\Models\KuotaCuti;
use App\Models\PengajuanCuti;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CutiStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $currentYear = now()->year;
        $currentMonth = now()->month;

        $kuotaCuti = KuotaCuti::where('user_id', $user->id)
            ->where('tahun', $currentYear)
            ->first();

        $pengajuanBulanIni = PengajuanCuti::where('user_id', $user->id)
            ->whereYear('tanggal_mulai', $currentYear)
            ->whereMonth('tanggal_mulai', $currentMonth)
            ->count();

        $cutiDisetujui = PengajuanCuti::where('user_id', $user->id)
            ->whereYear('tanggal_mulai', $currentYear)
            ->where('status', 'approved')
            ->count();

        $cutiPending = PengajuanCuti::where('user_id', $user->id)
            ->whereYear('tanggal_mulai', $currentYear)
            ->where('status', 'pending')
            ->count();

        $sisaKuota = $kuotaCuti ? $kuotaCuti->kuota_tersisa : PengajuanCuti::KUOTA_CUTI_TAHUNAN;
        $kuotaTerpakai = $kuotaCuti ? $kuotaCuti->kuota_terpakai : 0;
        $sisaPengajuanBulanIni = PengajuanCuti::MAX_PENGAJUAN_PER_BULAN - $pengajuanBulanIni;

        return [
            Stat::make('Kuota Cuti Tersisa', $sisaKuota . ' hari')
                ->description('dari ' . PengajuanCuti::KUOTA_CUTI_TAHUNAN . ' hari per tahun')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($sisaKuota <= 3 ? 'danger' : ($sisaKuota <= 6 ? 'warning' : 'success'))
                ->chart($this->getKuotaChart($kuotaTerpakai, $sisaKuota)),

            Stat::make('Cuti Terpakai', $kuotaTerpakai . ' hari')
                ->description($cutiDisetujui . ' cuti disetujui tahun ini')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('Sisa Pengajuan Bulan Ini', $sisaPengajuanBulanIni . ' kali')
                ->description('dari max ' . PengajuanCuti::MAX_PENGAJUAN_PER_BULAN . ' kali per bulan')
                ->descriptionIcon('heroicon-m-document-text')
                ->color($sisaPengajuanBulanIni === 0 ? 'danger' : ($sisaPengajuanBulanIni === 1 ? 'warning' : 'success')),

            Stat::make('Cuti Pending', $cutiPending . ' pengajuan')
                ->description('Menunggu persetujuan HRD')
                ->descriptionIcon('heroicon-m-clock')
                ->color($cutiPending > 0 ? 'warning' : 'gray'),
        ];
    }

    protected function getKuotaChart(int $terpakai, int $tersisa): array
    {
        return [
            $terpakai,
            $terpakai,
            $terpakai,
            $terpakai,
            $terpakai,
            $terpakai,
            $terpakai,
        ];
    }

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('staff');
    }
}
