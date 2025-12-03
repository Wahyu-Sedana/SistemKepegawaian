<?php

namespace App\Filament\Resources\KuotaCutiResource\Pages;

use App\Filament\Resources\KuotaCutiResource;
use App\Models\PengajuanCuti;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;

class ViewKuotCuti extends ViewRecord
{
    protected static string $resource = KuotaCutiResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Kuota Cuti')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Nama Pegawai')
                                    ->visible(fn() => auth()->user()->hasRole(['super_admin', 'HRD'])),

                                TextEntry::make('tahun')
                                    ->label('Tahun'),

                                TextEntry::make('kuota_awal')
                                    ->label('Kuota Awal')
                                    ->suffix(' hari')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('kuota_terpakai')
                                    ->label('Kuota Terpakai')
                                    ->suffix(' hari')
                                    ->badge()
                                    ->color('warning'),

                                TextEntry::make('kuota_tersisa')
                                    ->label('Kuota Tersisa')
                                    ->suffix(' hari')
                                    ->badge()
                                    ->color(fn($record) => $record->kuota_tersisa <= 3 ? 'danger' : 'success')
                                    ->weight('bold'),

                                TextEntry::make('persentase_penggunaan')
                                    ->label('Persentase Penggunaan')
                                    ->state(function ($record) {
                                        if ($record->kuota_awal == 0) return '0%';
                                        return round(($record->kuota_terpakai / $record->kuota_awal) * 100, 1) . '%';
                                    })
                                    ->badge()
                                    ->color(function ($record) {
                                        $persentase = ($record->kuota_terpakai / max($record->kuota_awal, 1)) * 100;
                                        if ($persentase >= 80) return 'danger';
                                        if ($persentase >= 50) return 'warning';
                                        return 'success';
                                    }),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Riwayat Pengajuan Cuti')
                    ->schema([
                        TextEntry::make('riwayat_cuti')
                            ->label('')
                            ->state(function ($record) {
                                $cutis = PengajuanCuti::where('user_id', $record->user_id)
                                    ->whereYear('tanggal_mulai', $record->tahun)
                                    ->orderBy('tanggal_mulai', 'desc')
                                    ->get();

                                if ($cutis->isEmpty()) {
                                    return 'Belum ada riwayat cuti untuk tahun ini.';
                                }

                                $html = '<div class="space-y-3">';
                                foreach ($cutis as $cuti) {
                                    $statusColor = match ($cuti->status) {
                                        'approved' => 'text-green-600 bg-green-50',
                                        'rejected' => 'text-red-600 bg-red-50',
                                        default => 'text-yellow-600 bg-yellow-50',
                                    };

                                    $html .= '<div class="p-4 border rounded-lg">';
                                    $html .= '<div class="flex justify-between items-start mb-2">';
                                    $html .= '<div>';
                                    $html .= '<div class="font-semibold text-gray-900">' . ucfirst($cuti->jenis_cuti) . '</div>';
                                    $html .= '<div class="text-sm text-gray-600">' . $cuti->tanggal_mulai->format('d M Y') . ' - ' . $cuti->tanggal_selesai->format('d M Y') . '</div>';
                                    $html .= '</div>';
                                    $html .= '<span class="px-3 py-1 rounded-full text-xs font-medium ' . $statusColor . '">' . ucfirst($cuti->status) . '</span>';
                                    $html .= '</div>';
                                    $html .= '<div class="text-sm text-gray-700"><strong>Jumlah Hari:</strong> ' . $cuti->jumlah_hari . ' hari</div>';
                                    $html .= '<div class="text-sm text-gray-700"><strong>Alasan:</strong> ' . $cuti->alasan . '</div>';
                                    if ($cuti->catatan_hrd) {
                                        $html .= '<div class="mt-2 text-sm text-gray-600"><strong>Catatan HRD:</strong> ' . $cuti->catatan_hrd . '</div>';
                                    }
                                    $html .= '</div>';
                                }
                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
