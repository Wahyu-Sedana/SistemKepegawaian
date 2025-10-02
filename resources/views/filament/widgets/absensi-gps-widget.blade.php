<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        🌍 Absensi GPS
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ now()->translatedFormat('l, d F Y') }}
                    </p>
                </div>

                <div class="text-right">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ now()->format('H:i') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        WIB
                    </div>
                </div>
            </div>

            <!-- Status Card -->
            @if ($sudahCheckIn)
                <div
                    class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-3">
                            <div class="bg-green-500 rounded-full p-2">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-green-900 dark:text-green-100">
                                    Check-in Berhasil
                                </h3>
                                <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                                    <strong>Waktu:</strong> {{ $absensi->jam_masuk?->format('H:i:s') }}
                                </p>
                                @if ($absensi->alamat_masuk)
                                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                                        📍 {{ $absensi->alamat_masuk }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        @if ($absensi->status === 'terlambat')
                            <span
                                class="bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-xs font-medium px-2.5 py-0.5 rounded">
                                Terlambat
                            </span>
                        @endif
                    </div>
                </div>
            @else
                <div
                    class="bg-gradient-to-r from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
                    <div class="flex items-center space-x-3">
                        <div class="bg-orange-500 rounded-full p-2">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-orange-900 dark:text-orange-100">
                                Belum Check-in
                            </h3>
                            <p class="text-sm text-orange-700 dark:text-orange-300">
                                Jangan lupa absen masuk hari ini
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Check-out Status -->
            @if ($sudahCheckIn && $sudahCheckOut)
                <div
                    class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                    <div class="flex items-start space-x-3">
                        <div class="bg-blue-500 rounded-full p-2">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-blue-900 dark:text-blue-100">
                                Check-out Berhasil
                            </h3>
                            <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                <strong>Waktu:</strong> {{ $absensi->jam_keluar?->format('H:i:s') }}
                            </p>
                            @if ($absensi->alamat_keluar)
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                    📍 {{ $absensi->alamat_keluar }}
                                </p>
                            @endif
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                                ✨ Terima kasih atas kerja keras Anda hari ini!
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($sudahCheckIn && !$sudahCheckOut)
                <div
                    class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900/20 dark:to-slate-900/20 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="bg-gray-500 rounded-full p-2">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                    Sedang Bekerja
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Jangan lupa check-out nanti
                                </p>
                            </div>
                        </div>

                        <div class="text-right">
                            @php
                                $jamMasuk = $absensi->jam_masuk;
                                $sekarang = now();
                                $durasi = $jamMasuk->diff($sekarang);
                            @endphp
                            <div class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $durasi->h }}j {{ $durasi->i }}m
                            </div>
                            <div class="text-xs text-gray-500">
                                Durasi kerja
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Info Lokasi Kantor -->
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3 text-sm">
                <div class="flex items-start space-x-2">
                    <svg class="w-4 h-4 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="text-gray-600 dark:text-gray-400">
                        <p class="font-medium">Tips Absensi:</p>
                        <ul class="list-disc list-inside mt-1 space-y-1 text-xs">
                            <li>Aktifkan GPS/Lokasi di perangkat Anda</li>
                            <li>Pastikan berada dalam radius {{ config('absensi.radius_maksimal', 100) }} meter dari
                                kantor</li>
                            <li>Check-in sebelum pukul {{ config('absensi.jam_masuk', '08:00') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
