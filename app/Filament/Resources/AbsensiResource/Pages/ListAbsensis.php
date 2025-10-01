<?php

namespace App\Filament\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use App\Models\Absensi;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;

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
                ->action(function () {
                    return null;
                });
        }

        if ($user->hasRole(['super_admin', 'HRD'])) {
            $actions[] = Actions\CreateAction::make();
        }

        return $actions;
    }

    protected function checkIn(): void
    {
        try {

            $latKantor = \App\Models\Setting::get('latitude', config('absensi.lokasi_kantor.latitude'));
            $lonKantor = \App\Models\Setting::get('longitude', config('absensi.lokasi_kantor.longitude'));
            $radiusMaksimal = \App\Models\Setting::get('radius', config('absensi.radius_maksimal'));


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
                $response = Http::get("https://nominatim.openstreetmap.org/reverse", [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'json'
                ]);
                $data = $response->json();
                $alamat = $data['display_name'] ?? 'Alamat tidak diketahui';
            } catch (\Exception $e) {
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
                $response = Http::get("https://nominatim.openstreetmap.org/reverse", [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'json'
                ]);
                $data = $response->json();
                $alamat = $data['display_name'] ?? 'Alamat tidak diketahui';
            } catch (\Exception $e) {
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
