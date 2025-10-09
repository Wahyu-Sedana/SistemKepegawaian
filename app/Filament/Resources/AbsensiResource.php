<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbsensiResource\Pages;
use App\Models\Absensi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $navigationGroup = 'Manajemen Absensi';
    protected static ?string $navigationLabel = 'Absensi';
    protected static ?string $pluralModelLabel = 'Absensi';
    protected static ?string $modelLabel = 'Absensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Absensi')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Karyawan')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn() => !auth()->user()->hasRole(['super_admin', 'HRD'])),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->default(today()),

                        Forms\Components\TimePicker::make('jam_masuk')
                            ->label('Jam Masuk')
                            ->seconds(false),

                        Forms\Components\TimePicker::make('jam_keluar')
                            ->label('Jam Keluar')
                            ->seconds(false),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'hadir' => 'Hadir',
                                'terlambat' => 'Terlambat',
                                'izin' => 'Izin',
                                'sakit' => 'Sakit',
                                'alpha' => 'Alpha',
                            ])
                            ->required()
                            ->default('hadir'),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->columnSpan('full')
                            ->rows(3),
                    ]),

                Forms\Components\Section::make('Lokasi Check-in')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('latitude_masuk')
                            ->label('Latitude')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('longitude_masuk')
                            ->label('Longitude')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('alamat_masuk')
                            ->label('Alamat')
                            ->columnSpan('full')
                            ->disabled(),

                        // Forms\Components\FileUpload::make('foto_masuk')
                        //     ->label('Foto Check-in')
                        //     ->image()
                        //     ->directory('absensi/masuk')
                        //     ->columnSpan('full')
                        //     ->disabled(),
                    ]),

                Forms\Components\Section::make('Lokasi Check-out')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('latitude_keluar')
                            ->label('Latitude')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('longitude_keluar')
                            ->label('Longitude')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('alamat_keluar')
                            ->label('Alamat')
                            ->columnSpan('full')
                            ->disabled(),

                        // Forms\Components\FileUpload::make('foto_keluar')
                        //     ->label('Foto Check-out')
                        //     ->image()
                        //     ->directory('absensi/keluar')
                        //     ->columnSpan('full')
                        //     ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jam_masuk')
                    ->label('Check-in')
                    ->time('H:i')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('jam_keluar')
                    ->label('Check-out')
                    ->time('H:i')
                    ->badge()
                    ->color('danger'),

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
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Input')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Tables\Columns\ImageColumn::make('foto_masuk')
                //     ->label('Foto Masuk')
                //     ->circular()
                //     ->defaultImageUrl(url('/images/no-image.png')),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\Filter::make('periode')
                    ->label('Periode Tampilan')
                    ->form([
                        Forms\Components\Select::make('value')
                            ->label('Pilih Periode')
                            ->options([
                                'harian' => 'Hari Ini',
                                'semua' => 'Tampilkan Semua',
                            ])
                            ->default('harian')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value']) && $data['value'] === 'harian') {
                            return $query->whereDate('tanggal', today());
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!isset($data['value']) || $data['value'] === 'semua') {
                            return 'Menampilkan: Semua Data';
                        }
                        return 'Menampilkan: Hari Ini (' . today()->format('d M Y') . ')';
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'hadir' => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpha' => 'Alpha',
                    ]),

                Tables\Filters\Filter::make('tanggal')
                    ->label('Rentang Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()->hasRole(['super_admin', 'HRD'])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->hasRole(['super_admin', 'HRD'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->hasRole(['super_admin', 'HRD'])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbsensis::route('/'),
            // 'create' => Pages\CreateAbsensi::route('/create'),
            'edit' => Pages\EditAbsensi::route('/{record}/edit'),
            'view' => Pages\ViewAbsensi::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole(['super_admin', 'HRD'])) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }
}
