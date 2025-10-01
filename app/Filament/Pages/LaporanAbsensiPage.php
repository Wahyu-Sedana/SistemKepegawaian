<?php

namespace App\Filament\Pages;

use App\Models\Absensi;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LaporanAbsensiPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Laporan Absensi';
    protected static ?string $navigationGroup = 'Manajemen Absensi';
    protected static string $view = 'filament.pages.laporan-absensi-page';
    protected static ?int $navigationSort = 3;

    public ?array $data = [];
    public $tanggal_dari;
    public $tanggal_sampai;
    public $user_id;

    public function mount(): void
    {
        $this->tanggal_dari = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_sampai = now()->endOfMonth()->format('Y-m-d');

        if (auth()->user()->hasRole('Staff')) {
            $this->user_id = auth()->id();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Karyawan')
                    ->options(User::role('Staff')->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Semua Karyawan')
                    ->visible(fn() => auth()->user()->hasRole(['super_admin', 'HRD'])),

                DatePicker::make('tanggal_dari')
                    ->label('Dari Tanggal')
                    ->required()
                    ->default(now()->startOfMonth()),

                DatePicker::make('tanggal_sampai')
                    ->label('Sampai Tanggal')
                    ->required()
                    ->default(now()->endOfMonth()),
            ])
            ->statePath('data')
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('jam_masuk')
                    ->label('Check-in')
                    ->time('H:i')
                    ->badge()
                    ->color('success'),

                TextColumn::make('jam_keluar')
                    ->label('Check-out')
                    ->time('H:i')
                    ->badge()
                    ->color('danger'),

                TextColumn::make('durasi')
                    ->label('Durasi')
                    ->getStateUsing(function ($record) {
                        if (!$record->jam_masuk || !$record->jam_keluar) {
                            return '-';
                        }
                        $diff = $record->jam_masuk->diff($record->jam_keluar);
                        return $diff->h . 'j ' . $diff->i . 'm';
                    }),

                TextColumn::make('status')
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

                TextColumn::make('alamat_masuk')
                    ->label('Lokasi Check-in')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->striped();
    }

    protected function getTableQuery(): Builder
    {
        $query = Absensi::query()
            ->with('user')
            ->whereBetween('tanggal', [
                $this->tanggal_dari ?? now()->startOfMonth(),
                $this->tanggal_sampai ?? now()->endOfMonth(),
            ]);

        // Filter by user
        if ($this->user_id) {
            $query->where('user_id', $this->user_id);
        }

        if (auth()->user()->hasRole('Staff')) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public function getStats(): array
    {
        $query = clone $this->getTableQuery();

        $totalHadir = (clone $query)->whereIn('status', ['hadir', 'terlambat'])->count();
        $totalTerlambat = (clone $query)->where('status', 'terlambat')->count();
        $totalIzin = (clone $query)->whereIn('status', ['izin', 'sakit'])->count();
        $totalAlpha = (clone $query)->where('status', 'alpha')->count();

        return [
            ['label' => 'Total Hadir', 'value' => $totalHadir, 'color' => 'success'],
            ['label' => 'Terlambat', 'value' => $totalTerlambat, 'color' => 'warning'],
            ['label' => 'Izin/Sakit', 'value' => $totalIzin, 'color' => 'info'],
            ['label' => 'Alpha', 'value' => $totalAlpha, 'color' => 'danger'],
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'HRD', 'Staff']);
    }
}
