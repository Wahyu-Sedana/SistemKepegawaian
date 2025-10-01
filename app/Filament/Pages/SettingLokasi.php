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
    protected static ?string $title = 'Pengaturan Lokasi Kantor';

    protected static string $view = 'filament.pages.setting-lokasi';

    public $latitude;
    public $longitude;
    public $radius;
    public ?string $map = null;


    public function mount(): void
    {
        $this->form->fill([
            'latitude' => Setting::get('latitude', -8.65),
            'longitude' => Setting::get('longitude', 115.22),
            'radius' => Setting::get('radius', 100),
        ]);
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('latitude')->label('Latitude')->numeric()->required(),
            Forms\Components\TextInput::make('longitude')->label('Longitude')->numeric()->required(),
            Forms\Components\TextInput::make('radius')->label('Radius Maksimal (meter)')->numeric()->required(),
            Forms\Components\ViewField::make('map')
                ->view('components.setting-map')
                ->columnSpanFull()
                ->dehydrated(false),
        ]);
    }

    public function save()
    {
        $data = $this->form->getState();

        Setting::set('latitude', $data['latitude']);
        Setting::set('longitude', $data['longitude']);
        Setting::set('radius', $data['radius']);

        Notification::make()
            ->title('Pengaturan lokasi berhasil disimpan!')
            ->success()
            ->send();

        $this->dispatch('reload-map', [
            'lat' => $data['latitude'],
            'lon' => $data['longitude'],
        ]);
    }
}
