<div class="w-full" wire:ignore>
    @if ($latitude && $longitude)
        @php
            // Generate unique ID for this map instance
            $mapId = 'map-' . uniqid();
        @endphp

        <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div id="{{ $mapId }}" wire:ignore style="width: 100%; height: 500px;"></div>
        </div>

        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            <p>Koordinat: {{ $latitude }}, {{ $longitude }}</p>
            @if ($label ?? null)
                <p>Lokasi: {{ $label }}</p>
            @endif
        </div>

        @push('scripts')
            <script src="https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.js"></script>
            <link href="https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.css" rel="stylesheet" />

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof mapboxgl !== 'undefined') {
                        mapboxgl.accessToken = "{{ config('services.mapbox.token') }}";

                        const lat = {{ $latitude }};
                        const lon = {{ $longitude }};

                        const map = new mapboxgl.Map({
                            container: '{{ $mapId }}',
                            style: 'mapbox://styles/mapbox/streets-v12',
                            center: [lon, lat],
                            zoom: 15
                        });

                        new mapboxgl.Marker({
                                color: '#E02424'
                            })
                            .setLngLat([lon, lat])
                            .addTo(map);
                    }
                });
            </script>
        @endpush
    @else
        <div class="text-center py-8 text-gray-500">
            <p>Lokasi tidak tersedia</p>
        </div>
    @endif
</div>
