@php
    $latitude = old('latitude', $beneficiary?->latitude);
    $longitude = old('longitude', $beneficiary?->longitude);
    $isManual = old('save_manual_location', $beneficiary?->geocode_precision === 'manual');
@endphp

<div class="md:col-span-2 rounded-lg border border-sky-200 bg-sky-50 p-4">
    <div class="mb-3">
        <h3 class="font-medium text-sky-950">Map location</h3>
        <p class="text-sm text-sky-800">The address will be plotted when found. Drag the pin to correct it, or select a barangay to use its known centroid.</p>
    </div>
    <div id="location-geocode-status" class="mb-2 text-sm text-sky-800" aria-live="polite"></div>
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
        const addressInput = document.querySelector('[name="address"]');
        const status = document.getElementById('location-geocode-status');
        const centroids = @json($mapCentroids);
        const defaultCenter = [16.9400, 120.4550];
        const initial = latInput.value && lonInput.value ? [Number(latInput.value), Number(lonInput.value)] : defaultCenter;
        const hasInitialLocation = Boolean(latInput.value && lonInput.value);
        const map = L.map('location-picker-map').setView(initial, hasInitialLocation ? 16 : 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors', maxZoom: 19,
        }).addTo(map);
        const marker = L.marker(initial, {draggable: true}).addTo(map);
        const updateInputs = ({lat, lng}) => {
            latInput.value = lat.toFixed(7);
            lonInput.value = lng.toFixed(7);
        };
        const setLocation = (lat, lng, zoom = 17) => {
            const point = [Number(lat), Number(lng)];
            marker.setLatLng(point);
            map.setView(point, zoom);
            updateInputs({lat: point[0], lng: point[1]});
        };
        marker.on('dragend', () => updateInputs(marker.getLatLng()));
        barangaySelect?.addEventListener('change', () => {
            const centroid = centroids[barangaySelect.value];
            if (!centroid) return;
            setLocation(centroid.latitude, centroid.longitude, 15);
        });

        let geocodeTimer = null;
        let geocodeController = null;
        addressInput?.addEventListener('input', () => {
            window.clearTimeout(geocodeTimer);
            geocodeController?.abort();
            const address = addressInput.value.trim();
            if (address.length < 3) {
                status.textContent = '';
                return;
            }

            geocodeTimer = window.setTimeout(async () => {
                geocodeController = new AbortController();
                status.textContent = 'Finding address on the map...';
                const barangay = barangaySelect?.selectedOptions[0]?.textContent?.trim();
                const query = [address, barangay, 'Tagudin, Ilocos Sur, Philippines'].filter(Boolean).join(', ');
                try {
                    const response = await fetch(`/api/geocode-suggest?q=${encodeURIComponent(query)}`, {
                        headers: {Accept: 'application/json'},
                        signal: geocodeController.signal,
                    });
                    if (! response.ok) throw new Error('Geocoding request failed');
                    const result = (await response.json())[0];
                    if (! result || ! Number.isFinite(Number(result.lat)) || ! Number.isFinite(Number(result.lon))) {
                        const centroid = centroids[barangaySelect?.value];
                        if (centroid) {
                            setLocation(centroid.latitude, centroid.longitude, 15);
                            status.textContent = 'Address not listed. Showing the selected barangay location; drag the pin to the exact house.';
                        } else {
                            status.textContent = 'Address not found. Select a barangay or drag the pin to set the location.';
                        }
                        return;
                    }
                    setLocation(result.lat, result.lon);
                    status.textContent = 'Location found. Verify the pin and drag it if needed.';
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        status.textContent = 'Address lookup unavailable. Drag the pin to set the location.';
                    }
                }
            }, 700);
        });
        window.setTimeout(() => map.invalidateSize(), 0);
    });
</script>
