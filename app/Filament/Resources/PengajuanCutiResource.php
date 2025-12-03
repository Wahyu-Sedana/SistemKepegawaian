<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengajuanCutiResource\Pages;
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
use Carbon\Carbon;

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
        $userId = auth()->id();
        $tahun = Carbon::now()->year;
        $bulan = Carbon::now()->month;

        $sisaKuota = PengajuanCuti::getSisaKuotaCuti($userId, $tahun);
        $jumlahPengajuanBulanIni = PengajuanCuti::getJumlahPengajuanBulanIni($userId, $bulan, $tahun);
        $sisaPengajuanBulanIni = PengajuanCuti::MAX_PENGAJUAN_PER_BULAN - $jumlahPengajuanBulanIni;

        return $form
            ->schema([
                // Info Box untuk Staff
                Forms\Components\Section::make('Informasi Kuota Cuti Anda')
                    ->schema([
                        Forms\Components\Placeholder::make('info_kuota')
                            ->label('')
                            ->content(function () use ($sisaKuota, $sisaPengajuanBulanIni) {
                                $warningKuota = $sisaKuota <= 3 ? '⚠️ ' : '';
                                $warningPengajuan = $sisaPengajuanBulanIni <= 1 ? '⚠️ ' : '';

                                return new \Illuminate\Support\HtmlString(
                                    '<div class="space-y-2 text-sm">
                                        <div class="flex justify-between p-3 bg-blue-50 rounded-lg">
                                            <span class="font-semibold text-blue-900">Sisa Kuota Cuti Tahun Ini:</span>
                                            <span class="font-bold text-blue-600">' . $warningKuota . $sisaKuota . ' hari</span>
                                        </div>
                                        <div class="flex justify-between p-3 bg-green-50 rounded-lg">
                                            <span class="font-semibold text-green-900">Sisa Pengajuan Bulan Ini:</span>
                                            <span class="font-bold text-green-600">' . $warningPengajuan . $sisaPengajuanBulanIni . ' kali (max 3x/bulan)</span>
                                        </div>
                                    </div>'
                                );
                            }),
                    ])
                    ->visible(fn() => !auth()->user()->hasRole(['super_admin', 'HRD']))
                    ->collapsible(),

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
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $mulai = $state;
                                $selesai = $get('tanggal_selesai');

                                if ($mulai && $selesai) {
                                    $tanggalMulai = Carbon::parse($mulai);
                                    $tanggalSelesai = Carbon::parse($selesai);
                                    $jumlah = 0;

                                    // Hitung hari kerja (exclude Minggu)
                                    for ($tanggal = $tanggalMulai->copy(); $tanggal->lte($tanggalSelesai); $tanggal->addDay()) {
                                        if (!$tanggal->isSunday()) {
                                            $jumlah++;
                                        }
                                    }

                                    $set('jumlah_hari', $jumlah);
                                }
                            })
                            ->disabled(fn(string $operation): bool => $operation !== 'create'),

                        DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $mulai = $get('tanggal_mulai');
                                $selesai = $state;

                                if ($mulai && $selesai) {
                                    $tanggalMulai = Carbon::parse($mulai);
                                    $tanggalSelesai = Carbon::parse($selesai);
                                    $jumlah = 0;

                                    // Hitung hari kerja (exclude Minggu)
                                    for ($tanggal = $tanggalMulai->copy(); $tanggal->lte($tanggalSelesai); $tanggal->addDay()) {
                                        if (!$tanggal->isSunday()) {
                                            $jumlah++;
                                        }
                                    }

                                    $set('jumlah_hari', $jumlah);
                                }
                            })
                            ->disabled(fn(string $operation): bool => $operation !== 'create'),

                        Forms\Components\TextInput::make('jumlah_hari')
                            ->label('Jumlah Hari')
                            ->required()
                            ->numeric()
                            ->disabled()
                            ->helperText('Hari Minggu tidak dihitung')
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
                    ->label('Jenis Cuti')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'tahunan' => 'info',
                        'sakit' => 'warning',
                        'melahirkan' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('tanggal_selesai')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('jumlah_hari')
                    ->label('Jumlah')
                    ->suffix(' hari')
                    ->alignCenter()
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
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),

                Tables\Filters\SelectFilter::make('jenis_cuti')
                    ->label('Jenis Cuti')
                    ->options([
                        'tahunan' => 'Cuti Tahunan',
                        'sakit' => 'Cuti Sakit',
                        'melahirkan' => 'Cuti Melahirkan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if ($user->hasRole(['super_admin', 'HRD'])) {

            return (string) static::getModel()::where('status', 'pending')->count();
        }

        return (string) static::getModel()::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
