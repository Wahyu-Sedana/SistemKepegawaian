<div>
    @php
        // Ambil data dari form state atau dari Setting model
        $lat = $getState()['latitude'] ?? \App\Models\Setting::get('latitude', -8.670458);
        $lon = $getState()['longitude'] ?? \App\Models\Setting::get('longitude', 115.212631);
    @endphp

    @if ($lat && $lon)
        <!-- Google Maps Embed -->
        <div class="rounded-lg overflow-hidden border-2 border-gray-200 dark:border-gray-700 shadow-lg">
            <iframe id="map-iframe" width="100%" height="500" frameborder="0"
                src="https://maps.google.com/maps?q={{ $lat }},{{ $lon }}&hl=id&z=17&output=embed"
                class="w-full" loading="lazy"></iframe>
        </div>

        <!-- Info & Link -->
        <div class="mt-3 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p class="font-medium">📍 Koordinat Saat Ini:</p>
                <p class="text-xs mt-1" id="current-coords">{{ $lat }}, {{ $lon }}</p>
            </div>
            <a href="https://www.google.com/maps?q={{ $lat }},{{ $lon }}" target="_blank"
                id="gmaps-link"
                class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                <span>Buka di Google Maps</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </a>
        </div>


        @push('scripts')
            <script>
                // Update map iframe ketika koordinat berubah
                function updateMapDisplay(lat, lon) {
                    const iframe = document.getElementById('map-iframe');
                    const coords = document.getElementById('current-coords');
                    const link = document.getElementById('gmaps-link');

                    if (iframe) {
                        iframe.src = `https://maps.google.com/maps?q=${lat},${lon}&hl=id&z=17&output=embed`;
                    }
                    if (coords) {
                        coords.textContent = `${lat}, ${lon}`;
                    }
                    if (link) {
                        link.href = `https://www.google.com/maps?q=${lat},${lon}`;
                    }
                }

                document.addEventListener('DOMContentLoaded', function() {
                    const latInput = document.getElementById('latitude-input');
                    const lonInput = document.getElementById('longitude-input');

                    if (latInput && lonInput) {
                        let updateTimeout;

                        function handleInputChange() {
                            clearTimeout(updateTimeout);
                            updateTimeout = setTimeout(() => {
                                const lat = latInput.value;
                                const lon = lonInput.value;
                                if (lat && lon) {
                                    updateMapDisplay(lat, lon);
                                }
                            }, 1000); // Update setelah 1 detik user berhenti ketik
                        }

                        latInput.addEventListener('input', handleInputChange);
                        lonInput.addEventListener('input', handleInputChange);
                    }
                });
            </script>
        @endpush
    @else
        <div
            class="text-center py-12 bg-gray-50 dark:bg-gray-800/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <p class="text-gray-600 dark:text-gray-400 font-medium">Lokasi belum tersedia</p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Klik tombol "Ambil Lokasi GPS" di atas</p>
        </div>
    @endif
</div>
