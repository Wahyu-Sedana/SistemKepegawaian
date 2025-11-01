<?php

namespace App\Filament\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use App\Models\Absensi;
use App\Models\Setting;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ListAbsensis extends ListRecords
{
    protected static string $resource = AbsensiResource::class;
    protected static string $view = 'filament.resources.absensi-resource.pages.list-absensis';

    public $latitude;
    public $longitude;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $absensiHariIni = Absensi::absensiHariIni($user->id);

        $actions = [];

        if (!$absensiHariIni || !$absensiHariIni->jam_masuk) {
            $actions[] = Actions\Action::make('check_in')
                ->label('Check-in')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->color('success')
                ->extraAttributes(['onclick' => 'getLocationAndCheckIn()'])
                ->visible(fn() => !auth()->user()->hasRole(['super_admin']))
                ->action(function () {
                    return null;
                });
        }

        if ($absensiHariIni && $absensiHariIni->jam_masuk && !$absensiHariIni->jam_keluar) {
            $actions[] = Actions\Action::make('check_out')
                ->label('Check-out')
                ->icon('heroicon-o-arrow-left-on-rectangle')
                ->color('danger')
                ->extraAttributes(['onclick' => 'getLocationAndCheckOut()'])
                ->visible(fn() => !auth()->user()->hasRole(['super_admin']))
                ->action(function () {
                    return null;
                });
        }

        // if ($user->hasRole(['super_admin', 'HRD'])) {
        //     $actions[] = Actions\CreateAction::make();
        // }

        return $actions;
    }

    protected function checkIn(): void
    {
        try {
            $settingLat = Setting::where('key', 'latitude')->value('value');
            $settingLon = Setting::where('key', 'longitude')->value('value');
            $settingRadius = Setting::where('key', 'radius')->value('value');
            $latKantor = $settingLat ?? config('absensi.lokasi_kantor.latitude');
            $lonKantor = $settingLon ?? config('absensi.lokasi_kantor.longitude');
            $radiusMaksimal = $settingRadius ?? config('absensi.radius_maksimal');

            $latitude = $this->latitude;
            $longitude = $this->longitude;

            if (!$latitude || !$longitude) {
                Notification::make()
                    ->danger()
                    ->title('Gagal Check-in')
                    ->body('Tidak dapat mengambil lokasi GPS Anda. Pastikan GPS aktif dan izinkan akses lokasi.')
                    ->send();
                return;
            }

            try {
                $response = Http::withHeaders([
                    'User-Agent' => config('app.name', 'Laravel') . '/1.0'
                ])
                    ->timeout(10)
                    ->get("https://nominatim.openstreetmap.org/reverse", [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'format' => 'json',
                        'addressdetails' => 1,
                        'accept-language' => 'id'
                    ]);

                Log::info('Nominatim Response Status: ' . $response->status());
                Log::info('Nominatim Response Body: ' . $response->body());

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['display_name']) && !empty($data['display_name'])) {
                        $alamat = $data['display_name'];
                    } elseif (isset($data['address'])) {

                        $address = $data['address'];
                        $parts = array_filter([
                            $address['road'] ?? $address['pedestrian'] ?? '',
                            $address['suburb'] ?? $address['neighbourhood'] ?? '',
                            $address['city_district'] ?? '',
                            $address['city'] ?? $address['town'] ?? '',
                            $address['state'] ?? ''
                        ]);
                        $alamat = implode(', ', $parts);
                    } else {
                        $alamat = "Lat: {$latitude}, Lon: {$longitude}";
                    }
                } else {
                    Log::warning('Nominatim API failed: ' . $response->status());
                    $alamat = "Lat: {$latitude}, Lon: {$longitude}";
                }

                Log::info('Final alamat:', ['alamat' => $alamat]);
            } catch (\Exception $e) {
                Log::error('Geocoding error: ' . $e->getMessage());
                $alamat = "Lat: {$latitude}, Lon: {$longitude}";
            }

            $jarak = Absensi::hitungJarak($latKantor, $lonKantor, $latitude, $longitude);

            if ($jarak > $radiusMaksimal) {
                Notification::make()
                    ->danger()
                    ->title('Gagal Check-in')
                    ->body("Anda terlalu jauh dari kantor (" . round($jarak) . " meter). Maksimal radius: {$radiusMaksimal} meter.")
                    ->send();
                return;
            }

            $jamSekarang = now()->format('H:i:s');
            $jamMasukKantor = config('absensi.jam_masuk');
            $status = 'hadir';

            if ($jamSekarang > $jamMasukKantor) {
                $status = 'terlambat';
            }

            Absensi::create([
                'user_id' => auth()->id(),
                'tanggal' => today(),
                'jam_masuk' => now(),
                'latitude_masuk' => $latitude,
                'longitude_masuk' => $longitude,
                'alamat_masuk' => $alamat,
                'status' => $status,
            ]);

            Notification::make()
                ->success()
                ->title('Check-in Berhasil!')
                ->body("Selamat bekerja! Jarak dari kantor: " . round($jarak) . " meter")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Gagal Check-in')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->send();
        }
    }

    protected function checkOut(): void
    {
        try {
            $absensi = Absensi::absensiHariIni(auth()->id());

            if (!$absensi) {
                Notification::make()
                    ->danger()
                    ->title('Gagal Check-out')
                    ->body('Anda belum melakukan check-in hari ini.')
                    ->send();
                return;
            }


            $latitude = $this->latitude;
            $longitude = $this->longitude;

            if (!$latitude || !$longitude) {
                Notification::make()
                    ->danger()
                    ->title('Gagal Check-out')
                    ->body('Tidak dapat mengambil lokasi GPS Anda.')
                    ->send();
                return;
            }

            try {
                $response = Http::withHeaders([
                    'User-Agent' => config('app.name', 'Laravel') . '/1.0'
                ])
                    ->timeout(10)
                    ->get("https://nominatim.openstreetmap.org/reverse", [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'format' => 'json',
                        'addressdetails' => 1,
                        'accept-language' => 'id'
                    ]);

                Log::info('Nominatim Response Status: ' . $response->status());
                Log::info('Nominatim Response Body: ' . $response->body());

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['display_name']) && !empty($data['display_name'])) {
                        $alamat = $data['display_name'];
                    } elseif (isset($data['address'])) {
                        $address = $data['address'];
                        $parts = array_filter([
                            $address['road'] ?? $address['pedestrian'] ?? '',
                            $address['suburb'] ?? $address['neighbourhood'] ?? '',
                            $address['city_district'] ?? '',
                            $address['city'] ?? $address['town'] ?? '',
                            $address['state'] ?? ''
                        ]);
                        $alamat = implode(', ', $parts);
                    } else {
                        $alamat = "Lat: {$latitude}, Lon: {$longitude}";
                    }
                } else {
                    Log::warning('Nominatim API failed: ' . $response->status());
                    $alamat = "Lat: {$latitude}, Lon: {$longitude}";
                }

                Log::info('Final alamat:', ['alamat' => $alamat]);
            } catch (\Exception $e) {
                Log::error('Geocoding error: ' . $e->getMessage());
                $alamat = "Lat: {$latitude}, Lon: {$longitude}";
            }

            $absensi->update([
                'jam_keluar' => now(),
                'latitude_keluar' => $latitude,
                'longitude_keluar' => $longitude,
                'alamat_keluar' => $alamat,
            ]);

            Notification::make()
                ->success()
                ->title('Check-out Berhasil!')
                ->body('Terima kasih atas kerja keras Anda hari ini!')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Gagal Check-out')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->send();
        }
    }

    public function processCheckIn($lat, $lon): void
    {
        $this->latitude = $lat;
        $this->longitude = $lon;
        $this->checkIn();
    }

    public function processCheckOut($lat, $lon): void
    {
        $this->latitude = $lat;
        $this->longitude = $lon;
        $this->checkOut();
    }
}
