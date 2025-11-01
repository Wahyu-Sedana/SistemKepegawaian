<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenggajianResource\Pages;
use App\Models\Penggajian;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Infolist;

class PenggajianResource extends Resource
{
    protected static ?string $model = Penggajian::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Manajemen Kepegawaian';
    protected static ?string $navigationLabel = 'Penggajian';
    protected static ?string $pluralModelLabel = 'Data Penggajian';
    protected static ?string $modelLabel = 'Penggajian';

    public static function calculateNetSalary(Set $set, Get $get): void
    {
        $pokok    = (float) ($get('gaji_pokok') ?? 0);
        $tunjangan = (float) ($get('tunjangan') ?? 0);
        $potongan  = (float) ($get('potongan') ?? 0);

        $bersih = $pokok + $tunjangan - $potongan;

        $set('gaji_bersih', $bersih);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pegawai & Periode')
                    ->columns(3)
                    ->schema([
                        Select::make('user_id')
                            ->label('Pegawai')
                            ->relationship('user', 'name', fn(Builder $query) => $query->whereHas('roles', fn($q) => $q->where('name', 'Staff')))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        DatePicker::make('tanggal_gaji')
                            ->label('Tanggal Gaji Dibayarkan')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    // Periode adalah bulan SEBELUM tanggal pembayaran
                                    $periodeDate = \Carbon\Carbon::parse($state)->subMonth();
                                    $set('periode', $periodeDate->format('Y-m'));
                                }
                            }),

                        TextInput::make('periode')
                            ->label('Periode Absensi (YYYY-MM)')
                            ->readOnly()
                            ->helperText('Otomatis: bulan sebelum tanggal pembayaran'),
                    ]),

                Forms\Components\Section::make('Komponen Gaji')
                    ->columns(3)
                    ->schema([
                        TextInput::make('gaji_pokok')
                            ->label('Gaji Pokok')
                            ->numeric()
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateNetSalary($set, $get))
                            ->suffix('IDR'),

                        TextInput::make('tunjangan')
                            ->label('Total Tunjangan')
                            ->numeric()
                            ->default(0)
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateNetSalary($set, $get))
                            ->suffix('IDR'),

                        TextInput::make('potongan')
                            ->label('Total Potongan')
                            ->numeric()
                            ->default(0)
                            ->readOnly()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateNetSalary($set, $get))
                            ->suffix('IDR')
                            ->helperText('Potongan dihitung otomatis dari keterlambatan & ketidakhadiran'),

                        TextInput::make('gaji_bersih')
                            ->label('Gaji Bersih (Netto)')
                            ->numeric()
                            ->readOnly()
                            ->default(0)
                            ->dehydrated()
                            ->afterStateHydrated(fn(Set $set, Get $get) => self::calculateNetSalary($set, $get))
                            ->suffix('IDR')
                            ->columnSpanFull(),

                        Select::make('status')
                            ->options(['draft' => 'Draft', 'paid' => 'Sudah Dibayar'])
                            ->default('draft')
                            ->label('Status Pembayaran')
                            ->required(),
                    ])
                    ->footerActions([
                        Action::make('hitung_potongan')
                            ->label('Hitung Potongan Otomatis')
                            ->icon('heroicon-o-calculator')
                            ->color('warning')
                            ->visible(fn(Get $get) => $get('user_id') && $get('periode'))
                            ->action(function (Set $set, Get $get) {
                                $userId = $get('user_id');
                                $periode = $get('periode');

                                if (!$userId || !$periode) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Pilih pegawai dan periode terlebih dahulu')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                $result = Penggajian::hitungPotonganAbsensi($userId, $periode);

                                $set('potongan', $result['total']);
                                $set('detail_potongan', $result['details']);

                                self::calculateNetSalary($set, $get);

                                Notification::make()
                                    ->title('Potongan Berhasil Dihitung')
                                    ->body("Total Potongan: Rp " . number_format($result['total'], 0, ',', '.'))
                                    ->success()
                                    ->send();
                            })
                    ]),

                Forms\Components\Section::make('Detail Potongan')
                    ->schema([
                        Placeholder::make('detail_info')
                            ->label('')
                            ->content(function (Get $get) {
                                $details = $get('detail_potongan');

                                if (!$details || !is_array($details)) {
                                    return 'Klik "Hitung Potongan Otomatis" untuk melihat detail';
                                }

                                $summary = $details['summary'] ?? [];
                                $keterlambatan = $details['keterlambatan'] ?? [];
                                $tidakHadir = $details['tidak_hadir'] ?? [];
                                $periodeAbsensi = $details['periode_absensi'] ?? '-';

                                $html = '<div class="space-y-4">';

                                // Periode Info
                                $html .= '<div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-3 border-l-4 border-blue-500">';
                                $html .= '<p class="text-sm"><strong>Periode Absensi:</strong> ' . $periodeAbsensi . '</p>';
                                $html .= '</div>';

                                // Summary
                                $html .= '<div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">';
                                $html .= '<h4 class="font-semibold text-lg mb-2">Ringkasan Kehadiran</h4>';
                                $html .= '<ul class="space-y-1">';
                                $html .= '<li>Jumlah Hari Kerja: <strong>' . ($summary['jumlah_hari_kerja'] ?? 0) . ' hari</strong></li>';
                                $html .= '<li>Jumlah Hadir: <strong>' . ($summary['jumlah_hadir'] ?? 0) . ' hari</strong></li>';
                                $html .= '<li class="text-orange-600 dark:text-orange-400">Jumlah Keterlambatan: <strong>' . ($summary['jumlah_keterlambatan'] ?? 0) . ' kali</strong></li>';
                                $html .= '<li class="text-red-600 dark:text-red-400">Jumlah Tidak Hadir: <strong>' . ($summary['jumlah_tidak_hadir'] ?? 0) . ' hari</strong></li>';
                                $html .= '<li class="text-red-600 dark:text-red-400 mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">Total Potongan: <strong>Rp ' . number_format($summary['total_potongan'] ?? 0, 0, ',', '.') . '</strong></li>';
                                $html .= '</ul>';
                                $html .= '</div>';

                                // Keterlambatan
                                if (count($keterlambatan) > 0) {
                                    $html .= '<div class="rounded-lg bg-orange-50 dark:bg-orange-900/20 p-4">';
                                    $html .= '<h4 class="font-semibold mb-2 text-orange-800 dark:text-orange-200">Detail Keterlambatan (' . count($keterlambatan) . ' kali)</h4>';
                                    $html .= '<ul class="space-y-1 text-sm">';
                                    foreach ($keterlambatan as $item) {
                                        $html .= '<li><strong>' . $item['tanggal'] . '</strong><br>';
                                        $html .= '&nbsp;&nbsp;&nbsp;&nbsp; Jam Masuk: ' . $item['jam_masuk'] . ' (Terlambat <strong>' . $item['terlambat'] . '</strong>)<br>';
                                        $html .= '&nbsp;&nbsp;&nbsp;&nbsp; Potongan: Rp ' . number_format($item['potongan'], 0, ',', '.') . '</li>';
                                    }
                                    $html .= '</ul>';
                                    $html .= '</div>';
                                }

                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            })
                    ])
                    ->visible(fn(Get $get) => !empty($get('detail_potongan')))
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('periode')
                    ->label('Periode')
                    ->sortable(),

                TextColumn::make('gaji_pokok')
                    ->label('Gaji Pokok')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('tunjangan')
                    ->label('Tunjangan')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('potongan')
                    ->label('Potongan')
                    ->money('IDR')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(),

                TextColumn::make('gaji_bersih')
                    ->label('Gaji Bersih')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'warning',
                        'paid' => 'success',
                    }),

                TextColumn::make('tanggal_gaji')
                    ->label('Dibayarkan')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('download_bulk_pdf')
                    ->label('Download Rekapan')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('bulan')
                            ->label('Pilih Periode')
                            ->options(function () {
                                $periods = \App\Models\Penggajian::select('periode')
                                    ->distinct()
                                    ->orderBy('periode', 'desc')
                                    ->pluck('periode', 'periode');

                                return $periods->mapWithKeys(function ($periode) {
                                    $date = \Carbon\Carbon::createFromFormat('Y-m', $periode);
                                    return [$periode => $date->isoFormat('MMMM YYYY')];
                                });
                            })
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (array $data) {

                        $bulan = $data['bulan'];

                        $penggajians = \App\Models\Penggajian::with('user')
                            ->where('periode', $bulan)
                            ->where('status', 'paid')
                            ->get();

                        if ($penggajians->isEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tidak ada data')
                                ->body('Tidak ada data penggajian untuk periode tersebut')
                                ->warning()
                                ->send();
                            return;
                        }

                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.slip-gaji-bulk', compact('penggajians', 'bulan'));

                        $filename = 'Slip_Gaji_Periode_' . $bulan . '.pdf';

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, $filename);
                    })
                    ->modalSubmitActionLabel('Download PDF'),
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
            'index' => Pages\ListPenggajians::route('/'),
            'create' => Pages\CreatePenggajian::route('/create'),
            'edit' => Pages\EditPenggajian::route('/{record}/edit'),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Slip Gaji')
                    ->description('Periode Absensi: ' . $infolist->getRecord()->periode . ' | Dibayarkan: ' . $infolist->getRecord()->tanggal_gaji->format('d M Y'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Pegawai'),

                        TextEntry::make('periode')
                            ->label('Periode Absensi'),

                        TextEntry::make('gaji_pokok')
                            ->label('Gaji Pokok')
                            ->money('IDR'),

                        TextEntry::make('tunjangan')
                            ->label('Tunjangan')
                            ->money('IDR'),

                        TextEntry::make('potongan')
                            ->label('Potongan')
                            ->money('IDR')
                            ->color('danger'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                    ]),

                Section::make('Detail Potongan')
                    ->schema([
                        TextEntry::make('detail_potongan.summary.jumlah_hari_kerja')
                            ->label('Jumlah Hari Kerja')
                            ->suffix(' hari'),

                        TextEntry::make('detail_potongan.summary.jumlah_hadir')
                            ->label('Jumlah Hadir')
                            ->suffix(' hari')
                            ->color('success'),

                        TextEntry::make('detail_potongan.summary.jumlah_keterlambatan')
                            ->label('Jumlah Keterlambatan')
                            ->suffix(' kali')
                            ->color('warning'),

                        TextEntry::make('detail_potongan.summary.jumlah_tidak_hadir')
                            ->label('Jumlah Tidak Hadir')
                            ->suffix(' hari')
                            ->color('danger'),
                    ])
                    ->columns(4)
                    ->visible(fn($record) => !empty($record->detail_potongan)),

                Section::make('Total Pembayaran')
                    ->schema([
                        TextEntry::make('gaji_bersih')
                            ->label('GAJI BERSIH (NETTO)')
                            ->money('IDR')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color('success'),
                    ]),
            ]);
    }
}
