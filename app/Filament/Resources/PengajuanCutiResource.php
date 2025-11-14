<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengajuanCutiResource\Pages;
use App\Models\Absensi;
use App\Models\PengajuanCuti;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PengajuanCutiResource extends Resource
{
    protected static ?string $model = PengajuanCuti::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Manajemen Kepegawaian';
    protected static ?string $navigationLabel = 'Pengajuan Cuti';
    protected static ?string $pluralModelLabel = 'Pengajuan Cuti';
    protected static ?string $modelLabel = 'Pengajuan Cuti';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Pengajuan')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Pemohon')
                            ->relationship('user', 'name', fn(Builder $query) => $query->whereHas('roles', fn($q) => $q->where('name', 'Staff')))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn() => auth()->id())
                            ->hidden(fn() => !auth()->user()->hasRole(['super_admin', 'HRD'])),

                        Select::make('jenis_cuti')
                            ->label('Jenis Cuti')
                            ->options([
                                'tahunan' => 'Cuti Tahunan',
                                'sakit' => 'Cuti Sakit',
                                'melahirkan' => 'Cuti Melahirkan',
                            ])
                            ->required()
                            ->live()
                            ->disabled(fn(string $operation): bool => $operation !== 'create'),

                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->disabled(fn(string $operation): bool => $operation !== 'create'),

                        DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->disabled(fn(string $operation): bool => $operation !== 'create'),

                        FileUpload::make('bukti_surat')
                            ->label('Upload Bukti Sakit')
                            ->directory('bukti_surat')
                            ->image()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->visible(fn($get) => $get('jenis_cuti') === 'sakit')
                            ->required(fn($get) => $get('jenis_cuti') === 'sakit')
                            ->downloadable()
                            ->openable()
                            ->columnSpan('full'),

                        Forms\Components\TextInput::make('jumlah_hari')
                            ->label('Jumlah Hari')
                            ->required()
                            ->numeric()
                            ->disabled(fn(string $operation): bool => $operation !== 'create'),
                    ]),

                Textarea::make('alasan')
                    ->label('Alasan Pengajuan')
                    ->columnSpan('full')
                    ->required()
                    ->rows(3)
                    ->disabled(fn(string $operation): bool => $operation !== 'create'),

                Forms\Components\Section::make('Persetujuan (HRD/Admin)')
                    ->columns(2)
                    ->schema([
                        Select::make('disetujui_oleh_id')
                            ->label('Disetujui Oleh')
                            ->relationship(
                                'disetujuiOleh',
                                'name',
                                fn(Builder $query) =>
                                $query->whereHas('roles', fn($q) => $q->whereIn('name', ['super_admin', 'HRD']))
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->required(fn() => auth()->user()->hasRole(['super_admin', 'HRD']))
                            ->disabled(fn() => !auth()->user()->hasRole(['super_admin', 'HRD'])),

                        Select::make('status')
                            ->label('Status Persetujuan')
                            ->options([
                                'pending'  => 'Pending',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ])
                            ->required(fn() => auth()->user()->hasRole(['super_admin', 'HRD']))
                            ->default('pending')
                            ->disabled(fn() => !auth()->user()->hasRole(['super_admin', 'HRD'])),

                        Textarea::make('catatan_hrd')
                            ->label('Catatan HRD/Admin')
                            ->columnSpan('full')
                            ->nullable()
                            ->disabled(fn() => !auth()->user()->hasRole(['super_admin', 'HRD'])),
                    ]),


            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Pemohon')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jenis_cuti')
                    ->searchable(),

                TextColumn::make('tanggal_mulai')
                    ->date()
                    ->sortable(),

                TextColumn::make('tanggal_selesai')
                    ->date()
                    ->sortable(),

                TextColumn::make('jumlah_hari')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'secondary',
                    }),

                TextColumn::make('disetujuiOleh.name')
                    ->label('Disetujui Oleh')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengajuanCutis::route('/'),
            'create' => Pages\CreatePengajuanCuti::route('/create'),
            'edit' => Pages\EditPengajuanCuti::route('/{record}/edit'),
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
