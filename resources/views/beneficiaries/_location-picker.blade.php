@php
    $latitude = old('latitude', $beneficiary?->latitude);
    $longitude = old('longitude', $beneficiary?->longitude);
    $isManual = old('save_manual_location', $beneficiary?->geocode_precision === 'manual');
@endphp

<div class="md:col-span-2 rounded-lg border border-sky-200 bg-sky-50 p-4">
    <div class="mb-3">
        <h3 class="font-medium text-sky-950">Map location</h3>
        <p class="text-sm text-sky-800">Drag the pin to correct the location. Selecting a barangay centres the map on its known centroid.</p>
    </div>
    <div id="location-picker-map" class="h-80 rounded border border-sky-200 bg-slate-100"></div>
    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div><label class="block text-sm">Latitude</label><input id="location-latitude" name="latitude" value="{{ $latitude }}" readonly class="w-full border rounded bg-white px-3 py-2"></div>
        <div><label class="block text-sm">Longitude</label><input id="location-longitude" name="longitude" value="{{ $longitude }}" readonly class="w-full border rounded bg-white px-3 py-2"></div>
    </div>
    <label class="mt-3 flex items-center gap-2 text-sm font-medium text-sky-950">
        <input type="checkbox" name="save_manual_location" value="1" class="rounded border-gray-300" @checked($isManual)>
        Save this as a manually corrected location
    </label>
</div>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        const latInput = document.getElementById('location-latitude');
        const lonInput = document.getElementById('location-longitude');
        const barangaySelect = document.querySelector('[name="barangay_id"]');
        const centroids = @json($mapCentroids);
        const defaultCenter = [16.9400, 120.4550];
        const initial = latInput.value && lonInput.value ? [Number(latInput.value), Number(lonInput.value)] : defaultCenter;
        const map = L.map('location-picker-map').setView(initial, initial === defaultCenter ? 13 : 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors', maxZoom: 19,
        }).addTo(map);
        const marker = L.marker(initial, {draggable: true}).addTo(map);
        const updateInputs = ({lat, lng}) => {
            latInput.value = lat.toFixed(7);
            lonInput.value = lng.toFixed(7);
        };
        marker.on('dragend', () => updateInputs(marker.getLatLng()));
        barangaySelect?.addEventListener('change', () => {
            const centroid = centroids[barangaySelect.value];
            if (!centroid) return;
            const point = [centroid.latitude, centroid.longitude];
            marker.setLatLng(point);
            map.setView(point, 15);
            updateInputs({lat: centroid.latitude, lng: centroid.longitude});
        });
    });
</script>
