<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;

class SettingLokasi extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Lokasi Kantor';
    protected static ?string $title = 'Pengaturan Lokasi Kantor';
    protected static string $view = 'filament.pages.setting-lokasi';
    protected static ?int $navigationSort = 99;

    public $latitude;
    public $longitude;
    public $radius;
    public $jam_masuk;
    public $jam_keluar;
    public $nama_kantor;
    public $search_location;
    public ?string $map;

    public function mount(): void
    {
        $this->form->fill([
            'latitude' => Setting::get('latitude', -8.670458),
            'longitude' => Setting::get('longitude', 115.212631),
            'radius' => Setting::get('radius', 100),
            'jam_masuk' => Setting::get('jam_masuk', '08:00:00'),
            'jam_keluar' => Setting::get('jam_keluar', '17:00:00'),
            'nama_kantor' => Setting::get('nama_kantor', 'Kantor Pusat'),
        ]);
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Kantor')
                ->description('Nama dan identitas lokasi kantor')
                ->schema([
                    Forms\Components\TextInput::make('nama_kantor')
                        ->label('Nama Kantor')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Kantor Pusat PT. XYZ'),
                ])
                ->columns(1),

            Forms\Components\Section::make('Koordinat Lokasi Kantor')
                ->description('Cari lokasi di map, ambil GPS, atau isi koordinat manual')
                ->schema([
                    Forms\Components\ViewField::make('map')
                        ->view('components.setting-map')
                        ->columnSpanFull()
                        ->dehydrated(false),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('latitude')
                                ->label('Latitude')
                                ->numeric()
                                ->required()
                                ->step('any')
                                ->reactive()
                                ->afterStateUpdated(function ($state) {
                                    $this->dispatch('update-marker-from-input', [
                                        'lat' => $state,
                                        'lon' => $this->longitude
                                    ]);
                                })
                                ->extraAttributes(['id' => 'latitude-input'])
                                ->placeholder('-8.670458')
                                ->helperText('Isi manual atau gunakan map di atas'),

                            Forms\Components\TextInput::make('longitude')
                                ->label('Longitude')
                                ->numeric()
                                ->required()
                                ->step('any')
                                ->reactive()
                                ->afterStateUpdated(function ($state) {
                                    $this->dispatch('update-marker-from-input', [
                                        'lat' => $this->latitude,
                                        'lon' => $state
                                    ]);
                                })
                                ->extraAttributes(['id' => 'longitude-input'])
                                ->placeholder('115.212631')
                                ->helperText('Isi manual atau gunakan map di atas'),
                        ]),
                ]),

            Forms\Components\Section::make('Pengaturan Absensi')
                ->description('Atur radius dan jam kerja untuk validasi absensi')
                ->schema([
                    Forms\Components\TextInput::make('radius')
                        ->label('Radius Maksimal (meter)')
                        ->numeric()
                        ->required()
                        ->default(100)
                        ->suffix('meter')
                        ->helperText('Karyawan hanya bisa absen dalam radius ini dari kantor')
                        ->minValue(10)
                        ->maxValue(1000),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TimePicker::make('jam_masuk')
                                ->label('Jam Masuk')
                                ->required()
                                ->seconds(false)
                                ->default('08:00:00')
                                ->helperText('Batas waktu check-in tepat waktu'),

                            Forms\Components\TimePicker::make('jam_keluar')
                                ->label('Jam Keluar')
                                ->required()
                                ->seconds(false)
                                ->default('17:00:00')
                                ->helperText('Waktu standar check-out'),
                        ]),
                ]),
        ]);
    }

    public function save()
    {
        $data = $this->form->getState();

        Setting::set('latitude', $data['latitude']);
        Setting::set('longitude', $data['longitude']);
        Setting::set('radius', $data['radius']);
        Setting::set('jam_masuk', $data['jam_masuk']);
        Setting::set('jam_keluar', $data['jam_keluar']);
        Setting::set('nama_kantor', $data['nama_kantor']);

        Notification::make()
            ->title('Pengaturan berhasil disimpan!')
            ->body('Koordinat lokasi dan pengaturan absensi telah diperbarui')
            ->success()
            ->send();

        $this->dispatch('refresh-page');
    }

    // Method untuk update koordinat dari GPS
    public function updateFromGPS($lat, $lon): void
    {
        $this->form->fill([
            'latitude' => round($lat, 7),
            'longitude' => round($lon, 7),
        ]);

        Notification::make()
            ->title('Lokasi GPS berhasil diambil!')
            ->body("Koordinat: {$lat}, {$lon}")
            ->success()
            ->send();
    }

    public function updateFromSearch($lat, $lon, $address): void
    {
        $this->form->fill([
            'latitude' => round($lat, 7),
            'longitude' => round($lon, 7),
        ]);

        Notification::make()
            ->title('Lokasi berhasil dipilih!')
            ->body($address)
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'HRD']);
    }
}
