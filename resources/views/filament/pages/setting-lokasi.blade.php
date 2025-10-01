<x-filament-panels::page>
    {{ $this->form }}

    <div class="mt-4">
        <x-filament::button wire:click="save" color="success">
            Simpan Pengaturan
        </x-filament::button>
    </div>
</x-filament-panels::page>
