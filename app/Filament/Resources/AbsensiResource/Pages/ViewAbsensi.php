<?php

namespace App\Filament\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\ViewEntry;

class ViewAbsensi extends ViewRecord
{
    protected static string $resource = AbsensiResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Absensi')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Karyawan'),

                        TextEntry::make('tanggal')
                            ->label('Tanggal')
                            ->date('d F Y'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'hadir' => 'success',
                                'terlambat' => 'warning',
                                'izin' => 'info',
                                'sakit' => 'gray',
                                'alpha' => 'danger',
                                default => 'secondary',
                            }),

                        TextEntry::make('jam_masuk')
                            ->label('Jam Check-in')
                            ->time('H:i:s'),

                        TextEntry::make('jam_keluar')
                            ->label('Jam Check-out')
                            ->time('H:i:s'),

                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->columnSpan('full'),
                    ]),

                Section::make('Lokasi Check-in')
                    ->columns(2)
                    ->schema([
                        // ImageEntry::make('foto_masuk')
                        //     ->label('Foto')
                        //     ->height(200),

                        TextEntry::make('alamat_masuk')
                            ->label('Alamat')
                            ->columnSpan('full'),

                        TextEntry::make('latitude_masuk')
                            ->label('Latitude'),

                        TextEntry::make('longitude_masuk')
                            ->label('Longitude'),

                        ViewEntry::make('map_masuk')
                            ->label('Peta Lokasi')
                            ->view('filament.infolists.map-entry')
                            ->columnSpan('full')
                            ->viewData([
                                'latitude' => $this->record->latitude_masuk,
                                'longitude' => $this->record->longitude_masuk,
                                'label' => 'Check-in',
                            ]),
                    ])
                    ->visible(fn() => $this->record->latitude_masuk && $this->record->longitude_masuk),

                Section::make('Lokasi Check-out')
                    ->columns(2)
                    ->schema([
                        // ImageEntry::make('foto_keluar')
                        //     ->label('Foto')
                        //     ->height(200),

                        TextEntry::make('alamat_keluar')
                            ->label('Alamat')
                            ->columnSpan('full'),

                        TextEntry::make('latitude_keluar')
                            ->label('Latitude'),

                        TextEntry::make('longitude_keluar')
                            ->label('Longitude'),

                        ViewEntry::make('map_keluar')
                            ->label('Peta Lokasi')
                            ->view('filament.infolists.map-entry')
                            ->columnSpan('full')
                            ->viewData([
                                'latitude' => $this->record->latitude_keluar,
                                'longitude' => $this->record->longitude_keluar,
                                'label' => 'Check-out',
                            ]),
                    ])
                    ->visible(fn() => $this->record->latitude_keluar && $this->record->longitude_keluar),
            ]);
    }
}
