@php
    $lat = $getState()['latitude'] ?? \App\Models\Setting::get('latitude', -8.670458);
    $lon = $getState()['longitude'] ?? \App\Models\Setting::get('longitude', 115.212631);
@endphp

<div class="rounded-lg overflow-hidden border-2 border-gray-200 dark:border-gray-700 shadow-lg">
    <div id="map" wire:ignore style="width: 100%; height: 500px;"></div>
</div>

<!-- Info dan tombol lokasi -->
<div
    class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3 gap-3">
    <div class="text-sm text-gray-600 dark:text-gray-400">
        <p class="font-medium">Koordinat Saat Ini:</p>
        <p class="text-xs mt-1" id="current-coords">{{ $lat }}, {{ $lon }}</p>
    </div>

    <div class="flex gap-2 flex-wrap">
        <button id="btn-current-location"
            class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium bg-primary-600 text-white hover:bg-primary-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 20v4m0-4a8 8 0 100-16 8 8 0 000 16zm0-16V0m8 12h4m-4 0a8 8 0 00-16 0H0m20 0h4" />
            </svg>
            Lokasi Saya Sekarang
        </button>
    </div>
</div>

@push('scripts')
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css"
        rel="stylesheet" />

    <script>
        mapboxgl.accessToken = "{{ config('services.mapbox.token') }}";

        const lat = {{ $lat }};
        const lon = {{ $lon }};

        // Inisialisasi map
        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v12',
            center: [lon, lat],
            zoom: 15
        });

        // Tambahkan marker draggable
        const marker = new mapboxgl.Marker({
                draggable: true
            })
            .setLngLat([lon, lat])
            .addTo(map);

        // Update koordinat saat marker digeser
        marker.on('dragend', () => {
            const lngLat = marker.getLngLat();
            updateCoords(lngLat.lat, lngLat.lng);
        });

        // Fungsi update tampilan koordinat dan input hidden
        function updateCoords(lat, lon) {
            document.getElementById('current-coords').textContent = `${lat.toFixed(7)}, ${lon.toFixed(7)}`;


            // Update Livewire state langsung
            if (window.Livewire) {
                const livewireEl = document.querySelector('[wire\\:id]');
                if (livewireEl) {
                    const lw = Livewire.find(livewireEl.getAttribute('wire:id'));
                    lw.set('latitude', lat.toFixed(7));
                    lw.set('longitude', lon.toFixed(7));
                }
            }
        }


        // Tambahkan Geocoder (search bar)
        const geocoder = new MapboxGeocoder({
            accessToken: mapboxgl.accessToken,
            mapboxgl: mapboxgl,
            marker: false,
            placeholder: 'Cari lokasi atau alamat...',
            countries: 'id',
            language: 'id'
        });
        map.addControl(geocoder);

        // Saat user pilih lokasi dari geocoder
        geocoder.on('result', (e) => {
            const [lon, lat] = e.result.center;
            marker.setLngLat([lon, lat]);
            map.flyTo({
                center: [lon, lat],
                zoom: 16
            });
            updateCoords(lat, lon);
        });

        // 📍 Tombol "Lokasi Saya Sekarang"
        const btnCurrent = document.getElementById('btn-current-location');
        btnCurrent.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('Browser kamu tidak mendukung fitur lokasi.');
                return;
            }

            btnCurrent.disabled = true;
            btnCurrent.innerHTML = '🔄 Mengambil lokasi...';

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const {
                        latitude,
                        longitude
                    } = pos.coords;
                    marker.setLngLat([longitude, latitude]);
                    map.flyTo({
                        center: [longitude, latitude],
                        zoom: 16
                    });
                    updateCoords(latitude, longitude);
                    btnCurrent.innerHTML = '📍 Lokasi Saya Sekarang';
                    btnCurrent.disabled = false;
                },
                (err) => {
                    console.error('❌ Gagal mendapatkan lokasi:', err);
                    alert('Tidak bisa mengambil lokasi. Pastikan izin lokasi diaktifkan.');
                    btnCurrent.innerHTML = '📍 Lokasi Saya Sekarang';
                    btnCurrent.disabled = false;
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        });
    </script>
@endpush
