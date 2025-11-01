<?php

namespace App\Filament\Widgets;

use App\Models\Absensi;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AbsensiTodayWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Absensi::query()
                    ->whereDate('tanggal', today())
                    ->with('user')
                    ->latest('jam_masuk')
            )
            ->heading('Absensi Hari Ini - ' . now()->translatedFormat('l, d F Y'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('jam_masuk')
                    ->label('Check-in')
                    ->time('H:i')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-arrow-right-on-rectangle'),

                Tables\Columns\TextColumn::make('jam_keluar')
                    ->label('Check-out')
                    ->time('H:i')
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-m-arrow-left-on-rectangle')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('durasi')
                    ->label('Durasi')
                    ->getStateUsing(function ($record) {
                        if (!$record->jam_masuk || !$record->jam_keluar) {
                            return '-';
                        }
                        $diff = $record->jam_masuk->diff($record->jam_keluar);
                        return $diff->h . 'j ' . $diff->i . 'm';
                    })
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-clock'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'hadir' => 'success',
                        'terlambat' => 'warning',
                        'izin' => 'info',
                        'sakit' => 'gray',
                        'alpha' => 'danger',
                        default => 'secondary',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'hadir' => 'heroicon-m-check-circle',
                        'terlambat' => 'heroicon-m-clock',
                        'izin' => 'heroicon-m-document-text',
                        'sakit' => 'heroicon-m-heart',
                        'alpha' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('alamat_masuk')
                    ->label('Lokasi')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->alamat_masuk)
                    ->icon('heroicon-m-map-pin')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('jam_masuk', 'desc')
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-m-eye')
                    ->url(fn($record) => route('filament.admin.resources.absensis.view', $record))
                    ->color('info'),
            ])
            ->emptyStateHeading('Belum ada absensi hari ini')
            ->emptyStateDescription('Belum ada karyawan yang melakukan absensi')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    public static function canView(): bool
    {
        // Hanya tampil untuk Admin dan HRD
        return auth()->user()->hasRole(['super_admin', 'HRD']);
    }
}
