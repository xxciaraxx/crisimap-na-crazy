<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">GIS Map</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow rounded-lg p-4">
                <form id="filter-form" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3">
                    <input name="search" placeholder="Search name or code" class="border rounded px-3 py-2">
                    <select name="barangay_id" class="border rounded px-3 py-2">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $barangay)
                            <option value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                        @endforeach
                    </select>
                    <select name="assistance_type_id" class="border rounded px-3 py-2">
                        <option value="">All Assistance Types</option>
                        @foreach($assistanceTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="border rounded px-3 py-2">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Released">Released</option>
                        <option value="Completed">Completed</option>
                    </select>
                    <input type="date" name="from" class="border rounded px-3 py-2">
                    <input type="date" name="to" class="border rounded px-3 py-2">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded xl:col-span-6">Apply Filters</button>
                </form>
                <p id="map-status" class="mt-3 text-sm text-gray-500">Loading GIS layers...</p>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <div id="map" style="height: 650px;"></div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <script>
        const map = L.map('map').setView([16.9301, 120.3604], 12);
        const dataEndpoint = @json(route('map.data'));
        const boundaryEndpoint = @json(route('map.boundaries'));
        const form = document.getElementById('filter-form');
        const statusLine = document.getElementById('map-status');
        const clusters = L.markerClusterGroup({ chunkedLoading: true, showCoverageOnHover: false });
        const heatPoints = [];
        let heatLayer = null;
        let boundaryLayer = null;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const loadBoundaries = async () => {
            const response = await fetch(boundaryEndpoint);
            const geojson = await response.json();

            if (boundaryLayer) {
                boundaryLayer.remove();
            }

            if (geojson && geojson.features && geojson.features.length > 0) {
                boundaryLayer = L.geoJSON(geojson, {
                    style: {
                        color: '#2563eb',
                        weight: 2,
                        fillOpacity: 0.08,
                    },
                }).addTo(map);
            }
        };

        const loadData = async () => {
            statusLine.textContent = 'Loading GIS records...';
            clusters.clearLayers();
            heatPoints.length = 0;

            const params = new URLSearchParams(new FormData(form));
            const response = await fetch(`${dataEndpoint}?${params.toString()}`);
            const payload = await response.json();

            payload.beneficiaries.forEach((item) => {
                const marker = L.marker([item.latitude, item.longitude]);
                const amount = item.amount ? `PHP ${Number(item.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : 'PHP 0.00';
                const assistance = item.assistance_type?.name ?? 'N/A';
                const barangay = item.barangay?.name ?? 'Unassigned';
                marker.bindPopup(`
                    <div class="space-y-1">
                        <strong>${item.name}</strong><br>
                        <span>${assistance}</span><br>
                        <span>${barangay}</span><br>
                        <span>${item.status}</span><br>
                        <span>${amount}</span><br>
                        <a href="/beneficiaries/${item.id}" class="text-blue-600">View full record</a>
                    </div>
                `);
                clusters.addLayer(marker);
                heatPoints.push([Number(item.latitude), Number(item.longitude), item.amount ? Math.max(1, Math.min(5, Math.ceil(Number(item.amount) / 2000))) : 1]);
            });

            if (heatLayer) {
                heatLayer.remove();
            }
            if (window.L && L.heatLayer && heatPoints.length > 0) {
                heatLayer = L.heatLayer(heatPoints, {
                    radius: 28,
                    blur: 22,
                    maxZoom: 17,
                    minOpacity: 0.2,
                }).addTo(map);
            }

            map.addLayer(clusters);

            if (payload.beneficiaries.length > 0) {
                const bounds = clusters.getBounds();
                if (bounds.isValid()) {
                    map.fitBounds(bounds.pad(0.1));
                }
            }

            statusLine.textContent = `${payload.summary.count} geotagged record(s) loaded.`;
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            loadData();
        });

        loadBoundaries().then(loadData);
    </script>
</x-app-layout>
