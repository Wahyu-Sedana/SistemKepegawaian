<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KuotaCutiResource\Pages;
use App\Filament\Resources\KuotaCutiResource\Pages\ViewKuotCuti;
use App\Filament\Resources\ListKuotaCutiss\Pages\ListKuotaCutis;
use App\Models\KuotaCuti;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KuotaCutiResource extends Resource
{
    protected static ?string $model = KuotaCuti::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Manajemen Kepegawaian';
    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return auth()->user()->hasRole('super_admin') ? 'Data Cuti Karyawan' : 'Data Cuti Saya';
    }

    public static function getPluralModelLabel(): string
    {
        return auth()->user()->hasRole('super_admin') ? 'Data Cuti Karyawan' : 'Data Cuti Saya';
    }

    public static function getModelLabel(): string
    {
        return auth()->user()->hasRole('super_admin') ? 'Data Cuti Karyawan' : 'Data Cuti Saya';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kuota Cuti')
                    ->schema([
                        Forms\Components\TextInput::make('tahun')
                            ->label('Tahun')
                            ->disabled(),

                        Forms\Components\TextInput::make('kuota_awal')
                            ->label('Kuota Awal')
                            ->disabled()
                            ->suffix('hari'),

                        Forms\Components\TextInput::make('kuota_terpakai')
                            ->label('Kuota Terpakai')
                            ->disabled()
                            ->suffix('hari'),

                        Forms\Components\TextInput::make('kuota_tersisa')
                            ->label('Kuota Tersisa')
                            ->disabled()
                            ->suffix('hari'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => auth()->user()->hasRole('super_admin')),

                TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('kuota_awal')
                    ->label('Kuota Awal')
                    ->suffix(' hari')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('kuota_terpakai')
                    ->label('Terpakai')
                    ->suffix(' hari')
                    ->sortable()
                    ->alignCenter()
                    ->color('warning'),

                TextColumn::make('kuota_tersisa')
                    ->label('Tersisa')
                    ->suffix(' hari')
                    ->sortable()
                    ->alignCenter()
                    ->color(fn($record) => $record->kuota_tersisa <= 3 ? 'danger' : 'success')
                    ->weight('bold'),

                TextColumn::make('persentase_terpakai')
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
                    })
                    ->alignCenter(),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diupdate')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tahun', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Filter Tahun')
                    ->options(function () {
                        $currentYear = now()->year;
                        return [
                            $currentYear => $currentYear,
                            $currentYear - 1 => $currentYear - 1,
                            $currentYear - 2 => $currentYear - 2,
                        ];
                    })
                    ->default(now()->year),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->visible(fn() => !auth()->user()->hasRole('super_admin')),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Belum ada data cuti')
            ->emptyStateDescription('Data kuota cuti akan muncul setelah ada pengajuan cuti.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKuotaCutis::route('/'),
            'view' => ViewKuotCuti::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole('super_admin')) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        if (auth()->user()->hasRole('super_admin')) {
            return false;
        }
        return $record->user_id === auth()->id();
    }
}
