<div class="w-full">
    @if ($latitude && $longitude)
        <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <iframe width="100%" height="400" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"
                src="https://maps.google.com/maps?q={{ $latitude }},{{ $longitude }}&hl=id&z=16&output=embed"
                class="w-full"></iframe>
        </div>

        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            <p>📍 Koordinat: {{ $latitude }}, {{ $longitude }}</p>
            <a href="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}" target="_blank"
                class="text-primary-600 hover:underline">
                Buka di Google Maps →
            </a>
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            <p>Lokasi tidak tersedia</p>
        </div>
    @endif
</div>
