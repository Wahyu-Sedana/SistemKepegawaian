<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <x-filament::section>
            <x-slot name="heading">
                Filter Laporan
            </x-slot>

            <form wire:submit="$refresh" class="space-y-4">
                {{ $this->form }}

                <div class="flex gap-2">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        <x-filament::loading-indicator class="h-4 w-4" wire:loading wire:target="$refresh" />
                        <span wire:loading.remove wire:target="$refresh">Tampilkan</span>
                        <span wire:loading wire:target="$refresh">Memuat...</span>
                    </x-filament::button>

                    <x-filament::button color="gray" wire:click="$refresh">
                        Reset
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach ($this->getStats() as $stat)
                <x-filament::section class="bg-{{ $stat['color'] }}-50 dark:bg-{{ $stat['color'] }}-900/20">
                    <div class="text-center">
                        <div
                            class="text-3xl font-bold text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400">
                            {{ $stat['value'] }}
                        </div>
                        <div class="text-sm text-{{ $stat['color'] }}-800 dark:text-{{ $stat['color'] }}-200 mt-1">
                            {{ $stat['label'] }}
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>

        <!-- Table -->
        <x-filament::section>
            <x-slot name="heading">
                Data Absensi
            </x-slot>

            <x-slot name="headerEnd">
                <div class="text-sm text-gray-500">
                    Periode: {{ $tanggal_dari }} s/d {{ $tanggal_sampai }}
                </div>
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
