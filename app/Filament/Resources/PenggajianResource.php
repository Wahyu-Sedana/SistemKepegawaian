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
        $pokok = (float) ($get('gaji_pokok') ?? 0);
        $tunjangan = (float) ($get('tunjangan') ?? 0);
        $totalLembur = (float) ($get('total_lembur') ?? 0);
        $potongan = (float) ($get('potongan') ?? 0);

        $bersih = $pokok + $tunjangan + $totalLembur - $potongan;

        $set('gaji_bersih', $bersih);
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
                    ->description('Klik tombol "Hitung Otomatis" setelah mengisi Gaji Harian untuk menghitung gaji berdasarkan kehadiran')
                    ->columns(3)
                    ->schema([
                        TextInput::make('gaji_harian')
                            ->label('Gaji Harian')
                            ->numeric()
                            ->required()
                            ->live(debounce: 500)
                            ->helperText('Sistem bayar per hari hadir')
                            ->suffix('IDR')
                            ->columnSpan(1),

                        Forms\Components\Placeholder::make('info_gaji_pokok')
                            ->label('Total Gaji dari Kehadiran')
                            ->content(function (Get $get) {
                                $gajiPokok = $get('gaji_pokok') ?? 0;
                                $details = $get('detail_potongan');
                                $jumlahHadir = $details['summary']['jumlah_hadir'] ?? 0;

                                if ($gajiPokok > 0) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="text-lg font-semibold text-success-600 dark:text-success-400">' .
                                            'Rp ' . number_format($gajiPokok, 0, ',', '.') .
                                            '</div>' .
                                            '<div class="text-xs text-gray-500 mt-1">' .
                                            'Hadir ' . $jumlahHadir . ' hari × Rp ' . number_format($get('gaji_harian') ?? 0, 0, ',', '.') .
                                            '</div>'
                                    );
                                }
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="text-sm text-gray-500 italic">Klik tombol hitung otomatis</div>'
                                );
                            })
                            ->columnSpan(2),

                        Forms\Components\Hidden::make('gaji_pokok')
                            ->default(0)
                            ->dehydrated()
                            ->live(),

                        TextInput::make('tunjangan')
                            ->label('Total Tunjangan')
                            ->numeric()
                            ->default(0)
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateNetSalary($set, $get))
                            ->suffix('IDR'),

                        TextInput::make('total_jam_lembur')
                            ->label('Total Jam Lembur')
                            ->numeric()
                            ->default(0)
                            ->readOnly()
                            ->suffix('Jam')
                            ->helperText('Dihitung otomatis dari data absensi'),

                        TextInput::make('total_lembur')
                            ->label('Total Bayaran Lembur')
                            ->numeric()
                            ->default(0)
                            ->readOnly()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateNetSalary($set, $get))
                            ->suffix('IDR')
                            ->helperText('Rp 15.000 per jam'),

                        TextInput::make('potongan')
                            ->label('Total Potongan')
                            ->numeric()
                            ->default(0)
                            ->readOnly()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateNetSalary($set, $get))
                            ->suffix('IDR')
                            ->helperText('Dihitung otomatis'),

                        TextInput::make('gaji_bersih')
                            ->label('Gaji Bersih (Netto)')
                            ->numeric()
                            ->readOnly()
                            ->default(0)
                            ->dehydrated()
                            ->afterStateHydrated(fn(Set $set, Get $get) => self::calculateNetSalary($set, $get))
                            ->suffix('IDR')
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('warning_belum_hitung')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 p-3 border-l-4 border-yellow-500">' .
                                    '<p class="text-sm text-yellow-800 dark:text-yellow-200 font-semibold">PENTING!</p>' .
                                    '<p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">Klik tombol "Hitung Potongan & Lembur Otomatis" sebelum menyimpan data untuk mendapatkan perhitungan yang akurat!</p>' .
                                    '</div>'
                            ))
                            ->visible(fn(Get $get) => empty($get('detail_potongan')))
                            ->columnSpanFull(),

                        Select::make('status')
                            ->options(['draft' => 'Draft', 'paid' => 'Sudah Dibayar'])
                            ->default('draft')
                            ->label('Status Pembayaran')
                            ->required(),
                    ])
                    ->footerActions([
                        Action::make('hitung_otomatis')
                            ->label('Hitung Potongan & Lembur Otomatis')
                            ->icon('heroicon-o-calculator')
                            ->color('primary')
                            ->visible(fn(Get $get) => $get('user_id') && $get('periode') && $get('gaji_harian'))
                            ->action(function (Set $set, Get $get) {
                                $userId = $get('user_id');
                                $periode = $get('periode');
                                $gajiHarian = $get('gaji_harian');

                                if (!$userId || !$periode) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Pilih pegawai dan periode terlebih dahulu')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                if (!$gajiHarian || $gajiHarian <= 0) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Isi gaji harian terlebih dahulu')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Hitung potongan
                                $resultPotongan = Penggajian::hitungPotonganAbsensi($userId, $periode, $gajiHarian);

                                // Set gaji pokok = gaji dari kehadiran
                                $set('gaji_pokok', $resultPotongan['gaji_dari_kehadiran']);
                                $set('potongan', $resultPotongan['total']);
                                $set('detail_potongan', $resultPotongan['details']);

                                // Hitung lembur
                                $resultLembur = Penggajian::hitungLembur($userId, $periode);
                                $set('total_jam_lembur', $resultLembur['total_jam']);
                                $set('total_lembur', $resultLembur['total_nominal']);

                                // Update detail potongan dengan data lembur
                                $details = $get('detail_potongan');
                                $details['lembur'] = $resultLembur['details'];
                                $set('detail_potongan', $details);

                                self::calculateNetSalary($set, $get);

                                Notification::make()
                                    ->title('Perhitungan Berhasil')
                                    ->body(sprintf(
                                        "Hadir: %d hari | Gaji: Rp %s | Potongan Terlambat: Rp %s | Lembur: %d jam (Rp %s)",
                                        $resultPotongan['details']['summary']['jumlah_hadir'],
                                        number_format($resultPotongan['gaji_dari_kehadiran'], 0, ',', '.'),
                                        number_format($resultPotongan['total'], 0, ',', '.'),
                                        $resultLembur['total_jam'],
                                        number_format($resultLembur['total_nominal'], 0, ',', '.')
                                    ))
                                    ->success()
                                    ->send();
                            })
                    ]),

                Forms\Components\Section::make('Detail Perhitungan')
                    ->schema([
                        Placeholder::make('detail_info')
                            ->label('')
                            ->content(function (Get $get) {
                                $details = $get('detail_potongan');

                                if (!$details || !is_array($details)) {
                                    return 'Klik tombol "Hitung Potongan & Lembur Otomatis" untuk melihat detail';
                                }

                                $summary = $details['summary'] ?? [];
                                $keterlambatan = $details['keterlambatan'] ?? [];
                                $tidakHadir = $details['tidak_hadir'] ?? [];
                                $periodeAbsensi = $details['periode_absensi'] ?? '-';

                                // Data lembur
                                $lemburData = $details['lembur'] ?? [];
                                $lemburSummary = $lemburData['summary'] ?? [];
                                $dataLembur = $lemburData['data_lembur'] ?? [];

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
                                $html .= '<li class="text-green-600 dark:text-green-400">Jumlah Hadir: <strong>' . ($summary['jumlah_hadir'] ?? 0) . ' hari</strong></li>';
                                $html .= '<li class="text-red-600 dark:text-red-400">Jumlah Tidak Hadir: <strong>' . ($summary['jumlah_tidak_hadir'] ?? 0) . ' hari</strong></li>';
                                $html .= '<li class="text-orange-600 dark:text-orange-400">Jumlah Keterlambatan: <strong>' . ($summary['jumlah_keterlambatan'] ?? 0) . ' kali</strong></li>';
                                $html .= '<li class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">Gaji Harian: <strong>Rp ' . number_format($summary['gaji_harian'] ?? 0, 0, ',', '.') . '</strong></li>';
                                $html .= '<li class="text-green-600 dark:text-green-400 font-semibold">Gaji dari Kehadiran: <strong>Rp ' . number_format($summary['gaji_dari_kehadiran'] ?? 0, 0, ',', '.') . '</strong></li>';
                                $html .= '<li class="text-orange-600 dark:text-orange-400">Potongan Keterlambatan: <strong>Rp ' . number_format($summary['potongan_keterlambatan'] ?? 0, 0, ',', '.') . '</strong></li>';
                                $html .= '</ul>';
                                $html .= '</div>';

                                // Detail Lembur
                                if (count($dataLembur) > 0) {
                                    $html .= '<div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4">';
                                    $html .= '<h4 class="font-semibold mb-2 text-green-800 dark:text-green-200">Detail Lembur</h4>';
                                    $html .= '<p class="text-sm mb-2">Total: <strong>' . ($lemburSummary['total_jam_lembur'] ?? 0) . ' jam</strong> × Rp 15.000 = <strong>Rp ' . number_format($lemburSummary['total_nominal'] ?? 0, 0, ',', '.') . '</strong></p>';
                                    $html .= '<ul class="space-y-1 text-sm">';
                                    foreach ($dataLembur as $item) {
                                        $html .= '<li><strong>' . $item['tanggal'] . '</strong>: ' . $item['jam_lembur'] . ' jam (Rp ' . number_format($item['nominal'], 0, ',', '.') . ')</li>';
                                    }
                                    $html .= '</ul>';
                                    $html .= '</div>';
                                }

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
                    ->label('Gaji Kehadiran')
                    ->money('IDR')
                    ->sortable()
                    ->description(fn($record) => 'Hadir: ' . ($record->detail_potongan['summary']['jumlah_hadir'] ?? '-') . ' hari'),

                TextColumn::make('total_lembur')
                    ->label('Lembur')
                    ->money('IDR')
                    ->sortable()
                    ->color('success')
                    ->toggleable(),

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
                            ->label('Gaji dari Kehadiran (' . ($infolist->getRecord()->detail_potongan['summary']['jumlah_hadir'] ?? 0) . ' hari)')
                            ->money('IDR')
                            ->color('success'),

                        TextEntry::make('gaji_harian')
                            ->label('Gaji Harian')
                            ->money('IDR'),

                        TextEntry::make('tunjangan')
                            ->label('Tunjangan')
                            ->money('IDR'),

                        TextEntry::make('total_lembur')
                            ->label('Lembur (' . ($infolist->getRecord()->total_jam_lembur ?? 0) . ' jam)')
                            ->money('IDR')
                            ->color('success'),

                        TextEntry::make('potongan')
                            ->label('Potongan')
                            ->money('IDR')
                            ->color('danger'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                    ]),

                Section::make('Ringkasan Kehadiran')
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
