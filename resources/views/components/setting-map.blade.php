<div id="map" style="height: 400px;"></div>

<script>
    let map, marker;

    function initMap(lat, lon) {
        if (map) {
            map.remove(); // reset map lama
        }

        map = L.map('map').setView([lat, lon], 17);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 20,
        }).addTo(map);

        marker = L.marker([lat, lon], {
            draggable: true
        }).addTo(map);

        const latInput = document.querySelector('input[name="latitude"]');
        const lonInput = document.querySelector('input[name="longitude"]');

        function updateMarker() {
            let newLat = parseFloat(latInput.value);
            let newLon = parseFloat(lonInput.value);
            if (!isNaN(newLat) && !isNaN(newLon)) {
                marker.setLatLng([newLat, newLon]);
                map.setView([newLat, newLon], 17);
            }
        }

        latInput.addEventListener("input", updateMarker);
        lonInput.addEventListener("input", updateMarker);

        marker.on('dragend', function(e) {
            let pos = marker.getLatLng();
            latInput.value = pos.lat.toFixed(6);
            lonInput.value = pos.lng.toFixed(6);
            latInput.dispatchEvent(new Event('input'));
            lonInput.dispatchEvent(new Event('input'));
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        initMap(
            @json(\App\Models\Setting::get('latitude', -8.65)),
            @json(\App\Models\Setting::get('longitude', 115.22))
        );
    });

    // ✅ Livewire v3: event listener
    document.addEventListener("livewire:reload-map", (event) => {
        initMap(event.detail.lat, event.detail.lon);
    });
</script>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
