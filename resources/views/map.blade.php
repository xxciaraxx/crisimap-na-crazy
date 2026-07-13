<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">GIS Map</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow rounded-lg p-4">
                <form method="GET" class="flex flex-wrap gap-3">
                    <select name="barangay_id" class="border rounded px-3 py-2">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $barangay)
                            <option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="border rounded px-3 py-2">
                        <option value="">All Status</option>
                        <option value="Pending" @selected(request('status') == 'Pending')>Pending</option>
                        <option value="Approved" @selected(request('status') == 'Approved')>Approved</option>
                        <option value="Released" @selected(request('status') == 'Released')>Released</option>
                        <option value="Completed" @selected(request('status') == 'Completed')>Completed</option>
                    </select>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded">Apply</button>
                </form>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <div id="map" style="height: 600px;"></div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script>
        const map = L.map('map').setView([16.9301, 120.3604], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
        const markers = L.markerClusterGroup();
        const beneficiaries = @json($beneficiaries->filter(fn($b) => $b->latitude && $b->longitude));
        beneficiaries.forEach(item => {
            const marker = L.marker([item.latitude, item.longitude]);
            marker.bindPopup(`<strong>${item.name}</strong><br>${item.assistance_type?.name ?? '—'}<br>${item.status}<br><a href="/beneficiaries/${item.id}">View full record</a>`);
            markers.addLayer(marker);
        });
        map.addLayer(markers);
    </script>
</x-app-layout>
