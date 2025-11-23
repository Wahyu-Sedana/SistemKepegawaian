<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserHistoryResource\Pages;
use App\Models\UserHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class UserHistoryResource extends Resource
{
    protected static ?string $model = UserHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Manajemen Kepegawaian';
    protected static ?string $navigationLabel = 'History Staff';
    protected static ?string $modelLabel = 'History Staff';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Read only - tidak ada form
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Staf')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('No. Telepon')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('deletedByUser.name')
                    ->label('Dihapus Oleh')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('deletion_reason')
                    ->label('Alasan')
                    ->placeholder('-')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 30) {
                            return $state;
                        }
                        return null;
                    }),
            ])
            ->defaultSort('deleted_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('deleted_at')
                    ->form([
                        Forms\Components\DatePicker::make('deleted_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('deleted_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['deleted_from'], fn($q, $date) => $q->whereDate('deleted_at', '>=', $date))
                            ->when($data['deleted_until'], fn($q, $date) => $q->whereDate('deleted_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail'),
            ])
            ->bulkActions([
                // Tidak ada bulk action
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Staf')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nama'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('No. Telepon')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('deleted_at')
                            ->label('Dihapus Pada')
                            ->dateTime('d M Y H:i'),
                        Infolists\Components\TextEntry::make('deletedByUser.name')
                            ->label('Dihapus Oleh')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('deletion_reason')
                            ->label('Alasan Penghapusan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Ringkasan Keseluruhan')
                    ->schema([
                        Infolists\Components\TextEntry::make('ringkasan.total_hadir')
                            ->label('Total Hadir')
                            ->default(0)
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('ringkasan.total_terlambat')
                            ->label('Total Terlambat')
                            ->default(0)
                            ->badge()
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('ringkasan.total_cuti')
                            ->label('Total Cuti')
                            ->default(0)
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('ringkasan.total_alpha')
                            ->label('Total Alpha')
                            ->default(0)
                            ->badge()
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('ringkasan.total_gaji_diterima')
                            ->label('Total Gaji Diterima')
                            ->money('IDR')
                            ->default(0),
                    ])
                    ->columns(5),

                Infolists\Components\Section::make('Data Per Bulan')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('user_data.monthly_data')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('periode_text')
                                    ->label('Periode')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->columnSpanFull(),

                                Infolists\Components\Section::make('Absensi')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('absensi.total_hadir')
                                            ->label('Hadir')
                                            ->badge()
                                            ->color('success'),
                                        Infolists\Components\TextEntry::make('absensi.total_terlambat')
                                            ->label('Terlambat')
                                            ->badge()
                                            ->color('warning'),
                                        Infolists\Components\TextEntry::make('absensi.total_cuti')
                                            ->label('Cuti')
                                            ->badge()
                                            ->color('info'),
                                        Infolists\Components\TextEntry::make('absensi.total_alpha')
                                            ->label('Alpha')
                                            ->badge()
                                            ->color('danger'),
                                        Infolists\Components\TextEntry::make('absensi.total_jam_lembur')
                                            ->label('Jam Lembur')
                                            ->suffix(' jam'),
                                    ])
                                    ->columns(5),

                                Infolists\Components\Section::make('Penggajian')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('penggajian.gaji_pokok')
                                            ->label('Gaji Pokok')
                                            ->money('IDR')
                                            ->placeholder('-'),
                                        Infolists\Components\TextEntry::make('penggajian.tunjangan')
                                            ->label('Tunjangan')
                                            ->money('IDR')
                                            ->placeholder('-'),
                                        Infolists\Components\TextEntry::make('penggajian.total_lembur')
                                            ->label('Total Lembur')
                                            ->money('IDR')
                                            ->placeholder('-'),
                                        Infolists\Components\TextEntry::make('penggajian.potongan')
                                            ->label('Potongan')
                                            ->money('IDR')
                                            ->placeholder('-'),
                                        Infolists\Components\TextEntry::make('penggajian.gaji_bersih')
                                            ->label('Gaji Bersih')
                                            ->money('IDR')
                                            ->weight('bold')
                                            ->color('success')
                                            ->placeholder('-'),
                                    ])
                                    ->columns(5)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserHistories::route('/'),
            'view' => Pages\ViewUserHistory::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
