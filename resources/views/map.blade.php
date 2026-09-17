<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Beneficiary GIS Map</h2></x-slot>

    <div class="map-page {{ $fullMap ? 'is-full-map' : '' }}"><div class="{{ $fullMap ? '' : 'py-6' }}"><div class="{{ $fullMap ? '' : 'max-w-7xl mx-auto sm:px-6 lg:px-8' }}">
        <section class="map-shell">
            @unless($fullMap)
            <div class="map-toolbar">
                <div class="map-title-block"><span class="map-title-icon" aria-hidden="true">&#9678;</span><div><p class="map-eyebrow">AICS geographic distribution</p><h3>Tagudin beneficiary map</h3><p id="map-status">Loading GIS layers&hellip;</p></div></div>
                <div class="map-toolbar-actions"><a href="{{ route('map', ['full' => 1]) }}" class="map-control-button">Full map</a></div>
            </div>
            @else
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('map') }}" class="map-return-button">&larr; Return</a>
                <p id="map-status" class="sr-only">Loading GIS layers&hellip;</p>
            @endunless
            @unless($fullMap)
            <form id="filter-form" class="map-filters" hidden>
                <label>Search<input name="search" placeholder="Name or case ID"></label>
                <label>Barangay<select name="barangay_id"><option value="">All barangays</option>@foreach($barangays as $barangay)<option value="{{ $barangay->id }}">{{ $barangay->name }}</option>@endforeach</select></label>
                <input type="hidden" name="view" value="live"><input type="hidden" name="period" value=""><label>Status<select name="status"><option value="">All statuses</option><option>Pending</option><option>Approved</option><option>Released</option><option>Completed</option><option>Rejected</option></select></label>
                <label>From<input type="date" name="from"></label><label>To<input type="date" name="to"></label>
                <div class="period-filter"><label>Year<select id="period-year"><option value="">All years</option>@for($year = now()->year + 3; $year >= now()->year - 3; $year--)<option value="{{ $year }}">{{ $year }}</option>@endfor</select></label><div id="period-levels" hidden><label>Quarter<select id="period-quarter"><option value="">All year</option><option value="Q1">Q1 (Jan-Mar)</option><option value="Q2">Q2 (Apr-Jun)</option><option value="Q3">Q3 (Jul-Sep)</option><option value="Q4">Q4 (Oct-Dec)</option></select></label><label>Week<select id="period-week"><option value="">All selected period</option></select></label><label>Day<input id="period-day" type="date"></label><button id="period-reset" type="button">Reset period</button></div></div>
                <button class="map-apply" type="submit">Apply filters</button>
            </form>
            @else
                <form id="filter-form" class="map-filters" hidden><input type="hidden" name="view" value="live"><input type="hidden" name="period" value=""></form>
            @endunless
            <div class="map-stage"><div class="map-corner-label"><span>TAGUDIN, ILOCOS SUR</span><b>Beneficiary distribution</b></div><div id="map" aria-label="Interactive map of Tagudin beneficiaries"></div><div class="map-compass" id="map-compass" role="group" aria-label="Map rotation controls"><span class="compass-n">N</span><span class="compass-e">E</span><span class="compass-s">S</span><span class="compass-w">W</span><div class="compass-rose" aria-hidden="true">✦</div><div class="compass-actions"><button type="button" data-rotate="-15" aria-label="Rotate map counterclockwise">↶</button><button type="button" data-reset-rotation aria-label="Reset map rotation">N</button><button type="button" data-rotate="15" aria-label="Rotate map clockwise">↷</button></div></div>
                <aside class="map-legend" id="map-legend"><div class="legend-heading"><div><strong>Assistance type</strong><p class="legend-subtitle">Marker colors only. Barangays use light map fills.</p></div><button id="legend-toggle" type="button" aria-label="Collapse assistance legend" title="Collapse assistance legend" aria-expanded="true">−</button></div><div id="legend-items"></div><button id="boundary-toggle" class="boundary-toggle" type="button">Hide barangay labels</button></aside>
            </div>
        </section>
    </div></div></div>

    <script>
    window.addEventListener('DOMContentLoaded', () => {
        const map = L.map('map', { zoomControl: false, maxBoundsViscosity: 1 }).setView([16.9400, 120.4550], 13);
        let mapBearing = 0;
        const compass = document.getElementById('map-compass');
        function setMapBearing(nextBearing) { mapBearing = (nextBearing % 360 + 360) % 360; map.getContainer().style.setProperty('--map-bearing', `${mapBearing}deg`); compass.style.setProperty('--compass-bearing', `${mapBearing}deg`); compass.setAttribute('aria-label', `Map rotation controls, currently ${mapBearing} degrees`); }
        compass.querySelectorAll('[data-rotate]').forEach(button => button.addEventListener('click', () => setMapBearing(mapBearing + Number(button.dataset.rotate))));
        compass.querySelector('[data-reset-rotation]').addEventListener('click', () => setMapBearing(0));
        setMapBearing(0);
        const streetMap = L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }
        );
        const satelliteMap = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            {
                attribution: 'Tiles &copy; Esri',
                maxNativeZoom: 17,
                maxZoom: 19,
            }
        );
        streetMap.addTo(map);
        const layerControl = L.control.layers({
            'Street Map': streetMap,
            'Satellite': satelliteMap,
        }, null, { position: 'topright' }).addTo(map);
        L.control.zoom({ position: 'bottomright' }).addTo(map);
        const endpoint = @json(route('map.data'));
        const boundaryEndpoint = @json(asset('geo/tagudin_barangays.geojson'));
        const geoLayerBase = @json(asset('geo'));
        const fullMap = @json($fullMap);
        const defaultCenter = [16.9301, 120.3604];
        const defaultZoom = 12;
        const form = document.getElementById('filter-form'), statusLine = document.getElementById('map-status');
        const categoryTypes = @json($assistanceTypes); let activeCategories = new Set(categoryTypes.map(type => String(type.id)));
        let allRecords = [], boundaryLayer, boundaryMask, barangayLabelsShown = true, tagudinBounds, recordsInitiallyFocused = false, satelliteActive = false;
        const clusters = L.layerGroup();
        map.addLayer(clusters);
        map.createPane('mask').style.zIndex = 350;
        map.createPane('barangays').style.zIndex = 400;
        map.createPane('barangay-labels').style.zIndex = 450;
        map.createPane('waterways').style.zIndex = 420;
        map.createPane('roads').style.zIndex = 430;
        map.createPane('buildings').style.zIndex = 440;

        function escapeHtml(value) { const div = document.createElement('div'); div.textContent = value ?? ''; return div.innerHTML; }
        function isPending(status) { return String(status).toLowerCase() === 'pending'; }
        function markerSize() { return map.getZoom() >= 16 ? 29 : map.getZoom() >= 14 ? 25 : 21; }
        function markerIcon(record) { const size = markerSize(), pending = isPending(record.status), history = form.elements.view.value === 'history', box = size + 12; return L.divIcon({ className: 'beneficiary-marker-wrap', iconSize: [box, box], iconAnchor: [box / 2, size + 8], html: `<span class="beneficiary-marker ${pending ? 'is-pending' : ''} ${history ? 'is-history' : ''}" style="--marker-color:${record.category.color};--marker-size:${size}px" aria-label="${escapeHtml(record.category.name)}"><b>${escapeHtml(record.category.icon)}</b></span>` }); }
        function statusClass(status) { return String(status).toLowerCase().replace(/\s+/g, '-'); }
        function popup(record) { const d = record.details, history = form.elements.view.value === 'history'; const amount = d.amount ? `₱${Number(d.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}` : 'Not recorded'; const date = history ? record.approved_at : record.date_availed; const isRejected = String(record.status).toLowerCase() === 'rejected'; const rejectionReason = isRejected ? (record.rejection_reason || '') : ''; return `<article class="beneficiary-popup" style="--category-color:${record.category.color}"><header><span class="category-icon">${escapeHtml(record.category.icon)}</span><div><b>${escapeHtml(record.category.name)}</b><small>Assistance category</small></div></header><dl><div><dt>Barangay</dt><dd>${escapeHtml(record.barangay || 'Unassigned')}</dd></div><div><dt>${history ? 'Date approved' : 'Date availed'}</dt><dd>${date ? new Date(date + 'T00:00:00').toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' }) : 'Not recorded'}</dd></div><div><dt>Status</dt><dd><span title="${escapeHtml(rejectionReason)}" class="status-badge ${statusClass(record.status)}">${escapeHtml(record.status)}</span></dd></div><div><dt>Beneficiary</dt><dd>${escapeHtml(d.beneficiary ?? d.name)}</dd></div><div><dt>Application reference</dt><dd>${escapeHtml(d.reference_code || 'Not assigned')}</dd></div><div><dt>Amount granted</dt><dd>${amount}</dd></div><div><dt>Social Officer</dt><dd>${escapeHtml(d.social_officer || 'Not assigned')}</dd></div></dl></article>`; }
        function clusterIcon(cluster) { const markers = cluster.getAllChildMarkers(), counts = {}; markers.forEach(marker => { const r = marker.options.record; counts[r.category.color] = (counts[r.category.color] || 0) + 1; }); const total = markers.length; let position = 0; const slices = Object.entries(counts).map(([color,count]) => { const end = position + count / total * 360; const slice = `${color} ${position}deg ${end}deg`; position = end; return slice; }).join(', '); return L.divIcon({ className:'category-cluster-wrap', iconSize:[44,44], iconAnchor:[22,22], html:`<span class="category-cluster" style="background:conic-gradient(${slices})"><b>${total}</b></span>` }); }
        function drawRecords() { clusters.clearLayers(); allRecords.filter(r => activeCategories.has(String(r.category.id))).forEach((record, index) => { const marker = L.marker([record.latitude, record.longitude], { icon: markerIcon(record), record, riseOnHover:true, keyboard:true }); marker.bindPopup(popup(record), { maxWidth: 280, className:'beneficiary-popup-wrap', closeButton:true, autoPanPadding:[24,24] }); marker.on('click', () => marker.getElement()?.classList.add('marker-pulse')); marker.on('popupclose', () => marker.getElement()?.classList.remove('marker-pulse')); marker.on('add', () => { const e = marker.getElement(); if (e && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) e.style.animationDelay = `${Math.random() * 300}ms`; }); clusters.addLayer(marker); }); statusLine.textContent = `${allRecords.filter(r => activeCategories.has(String(r.category.id))).length} geotagged record(s) shown.`; renderLegend(); }
        function renderLegend() {
            document.getElementById('legend-items').innerHTML = categoryTypes.map(type => { const count = allRecords.filter(record => String(record.category.id) === String(type.id)).length; return `<button class="legend-item ${activeCategories.has(String(type.id)) ? 'is-active' : 'is-hidden'}" data-category="${type.id}" type="button" aria-pressed="${activeCategories.has(String(type.id)) ? 'true' : 'false'}"><span class="legend-symbol" style="--marker-color:${type.color}">${escapeHtml(type.icon)}</span><span>${escapeHtml(type.name)}</span><strong class="legend-count" aria-label="${count} records">${count}</strong><i>✓</i></button>`; }).join('');
            document.querySelectorAll('.legend-item').forEach(button => button.addEventListener('click', () => {
                const id = button.dataset.category;
                activeCategories.has(id) ? activeCategories.delete(id) : activeCategories.add(id);
                renderLegend();
                drawRecords();
            }));
        }

        function resetMapView() {
            if (tagudinBounds) {
                map.fitBounds(tagudinBounds.pad(.08));
                map.invalidateSize(true);
            } else {
                map.setView(defaultCenter, defaultZoom);
            }
        }
        const barangayFills = ['#E8F4FF', '#FFF0F2', '#EDF8E7', '#FFF5DE', '#F2EDFF', '#E4F8F5', '#FCECF5', '#EAF0FA', '#FFF0E7', '#E8F8FC', '#F6EDFC', '#F1F7E6'];
        function barangayFill(feature) {
            const code = Number(feature.properties?.adm4_psgc ?? feature.id ?? 0);
            return barangayFills[Math.abs(code) % barangayFills.length];
        }
        function barangayStyle(feature, hovered = false) {
            return satelliteActive
                ? { color: hovered ? '#ffffff' : '#d8e8ef', weight: hovered ? 2.2 : 1, fillColor: '#ffffff', fillOpacity: 0, opacity: hovered ? 0.95 : 0.7 }
                : { color: hovered ? '#0F5FB8' : '#6D89A8', weight: hovered ? 2.25 : 1.35, fillColor: hovered ? '#dfeaff' : barangayFill(feature), fillOpacity: hovered ? 0.12 : 0.72, opacity: 1 };
        }
        function refreshBarangayStyles() {
            boundaryLayer?.eachLayer(layer => layer.setStyle(barangayStyle(layer.feature)));
        }
        function geoJsonRingToLeaflet(ring) {
            return ring.map(([longitude, latitude]) => [latitude, longitude]);
        }
        function featureToMaskHoles(feature) {
            const coordinates = feature.geometry?.coordinates ?? [];
            if (feature.geometry?.type === 'Polygon') {
                return coordinates.map(ring => geoJsonRingToLeaflet(ring));
            }
            if (feature.geometry?.type === 'MultiPolygon') {
                return coordinates.flatMap(polygon => polygon.map(ring => geoJsonRingToLeaflet(ring)));
            }
            return [];
        }
        function applyBoundaryMask(lockedFeature = null) {
            if (!boundaryLayer || !boundaryMask) return;
            const world = [[-90, -180], [-90, 180], [90, 180], [90, -180]];
            const features = boundaryLayer.toGeoJSON().features ?? [];
            const masks = lockedFeature ? features.filter(feature => feature !== lockedFeature) : features;
            const holes = masks.flatMap(featureToMaskHoles);
            boundaryMask.setLatLngs([world, ...holes]);
        }
        function addTagudinMask(geojson) {
            const world = [[-90, -180], [-90, 180], [90, 180], [90, -180]];
            const holes = (geojson.features ?? []).flatMap(featureToMaskHoles);
            boundaryMask = L.polygon([world, ...holes], {
                pane: 'mask',
                stroke: false,
                fillColor: '#f7fbff',
                fillOpacity: 1,
                interactive: false,
            }).addTo(map);
        }
        async function loadLocalLayer(fileName, layerName, style, pane) {
            const response = await fetch(`${geoLayerBase}/${fileName}`);
            if (!response.ok) throw new Error(`Unable to load ${fileName}`);
            const geojson = await response.json();
            const layer = L.geoJSON(geojson, { pane, style }).addTo(map);
            layerControl.addOverlay(layer, layerName);
            return layer;
        }
        async function loadSupportingLayers() {
            await Promise.all([
                loadLocalLayer('tagudin_building.geojson', 'Buildings', { color: '#2b2b2b', weight: 0.35, fillColor: '#d9d4d2', fillOpacity: 0.38, opacity: 0.7 }, 'buildings'),
                loadLocalLayer('tagudin_roads.geojson', 'Roads', { color: '#3f3f3f', weight: 1.8, opacity: 0.85, fillOpacity: 0 }, 'roads'),
                loadLocalLayer('tagudin_waterways.geojson', 'Waterways', { color: '#146fbe', weight: 2.2, opacity: 0.9, fillOpacity: 0 }, 'waterways'),
            ]);
        }
        async function loadBoundaries() {
            const response = await fetch(boundaryEndpoint);
            const geojson = await response.json();
            if (!geojson.features?.length) return;
            boundaryLayer = L.geoJSON(geojson, {
                pane: 'barangays',
                style: feature => barangayStyle(feature),
                onEachFeature: (feature, layer) => {
                    const name = feature.properties?.adm4_en || feature.properties?.name || 'Barangay';
                    if (!fullMap) layer.bindTooltip(name, { className: 'barangay-label', pane: 'barangay-labels', permanent: true, direction: 'center', opacity: 0.85 });
                    layer.on('mouseover', () => {
                        applyBoundaryMask(feature);
                        boundaryLayer.eachLayer(other => {
                            if (other !== layer) {
                                other.setStyle(barangayStyle(other.feature));
                            }
                        });
                        layer.setStyle(barangayStyle(feature, true));
                    });
                    layer.on('mouseout', () => {
                        applyBoundaryMask();
                        refreshBarangayStyles();
                    });
                    layer.on('click', () => {
                        const normalized = name.trim().toLowerCase();
                        const option = [...form.elements.barangay_id.options].find(item => item.text.trim().toLowerCase() === normalized);
                        if (option) {
                            form.elements.barangay_id.value = option.value;
                            form.dispatchEvent(new Event('submit', { bubbles: true }));
                        }
                    });
                },
            }).addTo(map);
            layerControl.addOverlay(boundaryLayer, 'Barangay boundaries');
            tagudinBounds = boundaryLayer.getBounds();
            map.setMaxBounds(tagudinBounds.pad(.04));
            addTagudinMask(geojson);
            boundaryLayer.bringToFront();
            resetMapView();
        }
        map.on('baselayerchange', event => {
            satelliteActive = event.layer === satelliteMap;
            refreshBarangayStyles();
        });
        let dataRequestInFlight = false;
        async function loadData() { if (dataRequestInFlight) return; dataRequestInFlight = true; statusLine.textContent = 'Updating map records…'; try { const response = await fetch(`${endpoint}?${new URLSearchParams(new FormData(form))}`, { headers: { Accept: 'application/json' } }); if (!response.ok) throw new Error('Map data request failed'); const payload = await response.json(); allRecords = payload.beneficiaries ?? []; drawRecords(); if (!recordsInitiallyFocused && allRecords.length) { const recordBounds = L.latLngBounds(allRecords.map(record => [record.latitude, record.longitude])); map.fitBounds(recordBounds.pad(allRecords.length === 1 ? 0.35 : 0.12), { maxZoom: 16 }); recordsInitiallyFocused = true; map.invalidateSize(true); } const missing = payload.summary?.missing_location_count ?? 0; if (missing) statusLine.textContent += ` ${missing} record(s) have no mapped location yet.`; } catch { statusLine.textContent = 'Unable to load map records. Please try again.'; } finally { dataRequestInFlight = false; } }
        document.getElementById('filter-toggle')?.addEventListener('click', () => { form.hidden = !form.hidden; });
        document.querySelectorAll('[data-view]').forEach(button => button.onclick = () => { form.elements.view.value = button.dataset.view; document.querySelectorAll('[data-view]').forEach(item => item.classList.toggle('is-active', item === button)); document.querySelector('.map-corner-label b').textContent = button.dataset.view === 'history' ? 'Approved Beneficiary History' : 'Beneficiary distribution'; loadData(); });
        const yearSelect = document.getElementById('period-year'), quarterSelect = document.getElementById('period-quarter'), weekSelect = document.getElementById('period-week'), dayInput = document.getElementById('period-day');
        function setPeriod(value) { form.elements.period.value = value; form.elements.from.value = ''; form.elements.to.value = ''; loadData(); }
        function weeksForYear() { weekSelect.innerHTML = '<option value="">All selected period</option>'; for (let week = 1; week <= 53; week++) weekSelect.insertAdjacentHTML('beforeend', `<option value="${String(week).padStart(2, '0')}">Week ${week}</option>`); }
        if (yearSelect) {
            yearSelect.onchange = () => { document.getElementById('period-levels').hidden = !yearSelect.value; weeksForYear(); setPeriod(yearSelect.value); };
            quarterSelect.onchange = () => setPeriod(quarterSelect.value ? `${yearSelect.value}-${quarterSelect.value}` : yearSelect.value);
            weekSelect.onchange = () => { if (weekSelect.value) setPeriod(`${yearSelect.value}-W${weekSelect.value}`); };
            dayInput.onchange = () => { if (dayInput.value) setPeriod(dayInput.value); };
        }
        document.getElementById('period-reset')?.addEventListener('click', () => { yearSelect.value = ''; quarterSelect.value = ''; weekSelect.value = ''; dayInput.value = ''; document.getElementById('period-levels').hidden = true; setPeriod(''); });
        document.getElementById('reset-map-view')?.addEventListener('click', resetMapView);
        document.getElementById('legend-toggle').onclick = event => { const legend = document.getElementById('map-legend'); legend.classList.toggle('is-collapsed'); const collapsed = legend.classList.contains('is-collapsed'); event.currentTarget.textContent = collapsed ? '+' : '−'; event.currentTarget.setAttribute('aria-expanded', String(!collapsed)); event.currentTarget.setAttribute('aria-label', collapsed ? 'Expand assistance legend' : 'Collapse assistance legend'); event.currentTarget.title = collapsed ? 'Expand assistance legend' : 'Collapse assistance legend'; };
        document.getElementById('boundary-toggle').onclick = event => { barangayLabelsShown = !barangayLabelsShown; boundaryLayer?.eachLayer(layer => barangayLabelsShown ? layer.openTooltip() : layer.closeTooltip()); event.currentTarget.textContent = barangayLabelsShown ? 'Hide barangay labels' : 'Show barangay labels'; };
        let filterTimer; form.addEventListener('input', () => { clearTimeout(filterTimer); filterTimer = setTimeout(loadData, 350); }); form.addEventListener('change', loadData); form.addEventListener('submit', event => { event.preventDefault(); loadData(); }); map.on('zoomend', drawRecords); setInterval(() => { if (document.visibilityState === 'visible' && form.elements.view.value === 'live') loadData(); }, 15000);
        renderLegend();
        Promise.all([loadBoundaries(), loadSupportingLayers()]).then(loadData).catch(loadData);
    });
    </script>
    <style>
    .map-shell{overflow:hidden;border:1px solid #dce8f7;border-radius:16px;background:#fff;box-shadow:0 12px 30px #073d8214}.map-toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.3rem 1.4rem;border-bottom:1px solid #e7eff8}.map-toolbar h3{margin:.12rem 0;color:#073d82;font-size:1.22rem}.map-eyebrow{margin:0;color:#1565c0;font-size:.67rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.map-toolbar p:last-child{margin:.25rem 0 0;color:#718198;font-size:.78rem}.map-control-button,.map-apply{border:0;border-radius:8px;padding:.65rem .9rem;color:#fff;background:#1565c0;font-size:.8rem;font-weight:800;cursor:pointer;box-shadow:0 4px 10px #1565c025}.map-filters{display:grid;grid-template-columns:1.5fr repeat(4,1fr) auto;gap:.75rem;align-items:end;padding:1rem 1.4rem;border-bottom:1px solid #e7eff8;background:#f7faff}.map-filters label{display:grid;gap:.3rem;color:#52627a;font-size:.72rem;font-weight:700}.map-filters input,.map-filters select{min-width:0;border:1px solid #cddcec;border-radius:7px;padding:.56rem .62rem;color:#274667;background:#fff;font-size:.8rem}.map-stage{position:relative}.map-stage #map{height:650px;background:#f7fbff}.leaflet-container{background:#f7fbff}.map-legend{position:absolute;z-index:500;bottom:18px;left:18px;width:240px;border:1px solid #d8e5f2;border-radius:11px;padding:.75rem;background:#fffffff2;box-shadow:0 10px 25px #073d8226;backdrop-filter:blur(7px)}.legend-heading{display:flex;align-items:flex-start;gap:.5rem;margin-bottom:.48rem;color:#073d82}.legend-heading>div{min-width:0;flex:1}.legend-heading strong{display:block;font-size:.88rem;margin-bottom:.18rem}.legend-subtitle{margin:0;color:#5b738c;font-size:.72rem;line-height:1.2}.legend-heading button{display:grid;flex:0 0 30px;place-items:center;width:30px;height:30px;border:1px solid #b9cee3;border-radius:8px;color:#0f5fb8;background:#eef6ff;font-size:1.25rem;font-weight:800;line-height:1;cursor:pointer}.legend-heading button:hover,.legend-heading button:focus-visible{color:#fff;background:#0f5fb8;outline:0}.legend-item{display:flex;align-items:center;gap:.55rem;width:100%;border:0;border-radius:8px;padding:.55rem .5rem;background:#fff;color:#38536f;font-size:.82rem;text-align:left;cursor:pointer;transition:background .18s ease,color .18s ease}.legend-item:hover{background:#eef4ff}.legend-item.is-active{background:#eaf3ff}.legend-item.is-hidden{opacity:.55}.legend-item i{margin-left:auto;color:#2e7d32;font-style:normal}.legend-symbol{display:inline-grid;place-items:center;width:1.25rem;height:1.25rem;border-radius:50%;background:var(--marker-color,#1565c0);color:#fff;font-size:.72rem;font-weight:800;box-shadow:0 1px 3px rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.65)}.legend-symbol::after{content:'';display:block;width:.5rem;height:.5rem;border-radius:50%;background:rgba(255,255,255,.45);opacity:.4}.legend-item.is-hidden i{visibility:hidden}.boundary-toggle{width:100%;margin-top:.55rem;border:1px solid #bdd2e8;border-radius:6px;padding:.4rem;color:#1565c0;background:#fff;font-size:.7rem;font-weight:700;cursor:pointer}.map-legend.is-collapsed #legend-items,.map-legend.is-collapsed .boundary-toggle{display:none}.beneficiary-marker-wrap{background:transparent!important;border:0!important}.beneficiary-marker{display:grid;place-items:center;width:var(--marker-size);height:var(--marker-size);border:2px solid #fff;border-radius:50%;color:#fff;background:var(--marker-color);box-shadow:0 2px 6px #052b5b75;transition:transform .18s ease-out,box-shadow .18s ease-out;animation:marker-in .28s ease-out both}.beneficiary-marker b{font-size:calc(var(--marker-size) * .58);line-height:1}.beneficiary-marker.is-pending{opacity:.54;background:#fff;color:var(--marker-color);border-color:var(--marker-color);box-shadow:0 0 0 2px #fff,0 2px 6px #052b5b50}.leaflet-marker-icon:hover .beneficiary-marker{transform:scale(1.2);box-shadow:0 4px 10px #052b5b8c}.marker-pulse .beneficiary-marker:after{position:absolute;inset:-6px;border:2px solid var(--marker-color);border-radius:50%;content:"";animation:marker-ring .45s ease-out}.category-cluster-wrap{background:transparent!important;border:0!important}.category-cluster{display:grid;place-items:center;width:40px;height:40px;border:2px solid #fff;border-radius:50%;box-shadow:0 3px 8px #052b5b70}.category-cluster b{display:grid;place-items:center;width:28px;height:28px;border-radius:50%;color:#073d82;background:#fff;font-size:.72rem}.beneficiary-popup-wrap .leaflet-popup-content-wrapper{border-radius:10px;padding:0;box-shadow:0 12px 30px #052b5b38}.beneficiary-popup-wrap .leaflet-popup-content{width:260px!important;margin:0}.beneficiary-popup{overflow:hidden;border-top:4px solid var(--category-color);color:#314c68}.beneficiary-popup header{display:flex;gap:.6rem;align-items:center;padding:.85rem .9rem .65rem;color:#073d82}.beneficiary-popup header small{display:block;margin-top:.1rem;color:#718198;font-size:.66rem}.category-icon{display:grid;place-items:center;width:28px;height:28px;border-radius:50%;color:#fff;background:var(--category-color);font-weight:800}.beneficiary-popup dl{display:grid;grid-template-columns:1fr 1fr;gap:.55rem;margin:0;padding:.15rem .9rem .85rem}.beneficiary-popup dl div{min-width:0}.beneficiary-popup dt{margin-bottom:.1rem;color:#7b8ca1;font-size:.62rem;font-weight:800;text-transform:uppercase}.beneficiary-popup dd{overflow:hidden;margin:0;color:#284561;font-size:.73rem;font-weight:700;text-overflow:ellipsis;white-space:nowrap}.status-badge{display:inline-block;border-radius:99px;padding:.17rem .42rem;font-size:.64rem}.status-badge.pending{color:#8a5b00;background:#fff3cd}.status-badge.approved,.status-badge.released,.status-badge.completed{color:#1d6b39;background:#e4f5e9}.case-link{display:flex;justify-content:space-between;padding:.65rem .9rem;color:#fff;background:#1565c0;font-size:.73rem;font-weight:800;text-decoration:none}.case-link:hover{background:#0d4f9b}.barangay-label{border:0!important;border-radius:4px!important;padding:2px 4px!important;color:#294d72!important;background:#fffffff0!important;font-size:10px!important;font-weight:700!important;box-shadow:0 1px 3px #052b5b20}@keyframes marker-in{from{opacity:0;transform:scale(.35)}to{opacity:1;transform:scale(1)}}@keyframes marker-ring{to{opacity:0;transform:scale(1.65)}}@media(max-width:700px){.map-toolbar{align-items:flex-start}.map-filters{grid-template-columns:1fr 1fr}.map-filters label:first-child{grid-column:1/-1}.map-apply{grid-column:1/-1}.map-stage #map{height:580px}.map-legend{bottom:14px;left:12px;width:190px}}@media(prefers-reduced-motion:reduce){.beneficiary-marker,.beneficiary-marker:after{animation:none!important;transition:none!important}}
    </style>
    <style>
    /* Operations-map redesign: the controls read as one calm workspace, while the map stays central. */
    .map-shell{border:0;border-radius:22px;background:#073d82;box-shadow:0 20px 48px rgba(7,61,130,.18)}
    .map-toolbar{min-height:110px;padding:1.5rem 1.65rem;border:0;color:#fff;background:linear-gradient(120deg,#06366f 0%,#1565c0 68%,#1b78cc 100%)}
    .map-title-block,.map-toolbar-actions{display:flex;align-items:center;gap:.85rem}.map-title-icon{display:grid;place-items:center;width:42px;height:42px;border:1px solid rgba(255,255,255,.38);border-radius:13px;color:#ffd54f;background:rgba(255,255,255,.12);font-size:1.55rem}.map-toolbar h3{margin:.12rem 0;color:#fff;font-size:1.35rem;letter-spacing:-.02em}.map-eyebrow{color:#b9dcff;font-size:.64rem}.map-toolbar p:last-child{color:#d7eaff}.map-live-indicator{display:flex;align-items:center;gap:.38rem;color:#dff3e5;font-size:.72rem;font-weight:800}.map-live-indicator i{width:7px;height:7px;border-radius:99px;background:#66d17d;box-shadow:0 0 0 4px rgba(102,209,125,.15)}
    .map-control-button{border:1px solid rgba(255,255,255,.28);border-radius:9px;padding:.65rem .85rem;background:rgba(255,255,255,.13);box-shadow:none}.map-control-button:hover{background:rgba(255,255,255,.24)}.map-view-toggle{display:flex;overflow:hidden;border:1px solid rgba(255,255,255,.28);border-radius:9px}.map-view-toggle button{border:0;padding:.58rem .72rem;color:#fff;background:transparent;font-size:.75rem;font-weight:700;cursor:pointer}.map-view-toggle button.is-active{color:#073d82;background:#fff}.period-filter{grid-column:1/-1;display:flex;gap:.75rem;align-items:end}.period-filter #period-levels{display:flex;gap:.75rem;align-items:end}.period-filter button{border:1px solid #cddcec;border-radius:7px;padding:.56rem .62rem;color:#274667;background:#fff;font-size:.8rem;cursor:pointer}.beneficiary-marker.is-history{opacity:.72;border-radius:3px;transform:rotate(45deg)}.beneficiary-marker.is-history b{transform:rotate(-45deg)}.status-badge.rejected{color:#9f1239;background:#ffe4e6}
    .map-filters{margin:0 1rem;border:0;border-radius:0 0 14px 14px;padding:1rem 1.15rem;background:#fff;box-shadow:0 10px 22px rgba(5,43,91,.12)}.map-filters input,.map-filters select{border-color:#d5e1ed;background:#f8fbfe}.map-apply{border-radius:7px;background:#0f5fb8;box-shadow:none}
    .map-stage{margin:1rem;border-radius:15px;overflow:hidden;border:5px solid #fff;background:#dceaf5;box-shadow:0 12px 24px rgba(3,35,76,.22)}.map-stage #map{height:680px}.map-corner-label{position:absolute;z-index:500;top:16px;left:16px;padding:.62rem .78rem;border-left:3px solid #fbc02d;border-radius:0 7px 7px 0;color:#38536f;background:rgba(255,255,255,.93);box-shadow:0 5px 16px rgba(7,61,130,.16)}.map-corner-label span{display:block;color:#1565c0;font-size:.56rem;font-weight:800;letter-spacing:.1em}.map-corner-label b{display:block;margin-top:.12rem;color:#073d82;font-size:.76rem}
    .map-legend{bottom:20px;left:auto;right:18px;width:225px;border:0;border-radius:13px;padding:.85rem;background:rgba(255,255,255,.96);box-shadow:0 12px 28px rgba(5,43,91,.25)}.legend-heading{padding-bottom:.48rem;border-bottom:1px solid #e5edf6}.legend-item{padding:.42rem .35rem}.legend-item:hover{background:#edf5ff}.legend-count{min-width:1.55rem;margin-left:auto;color:#155a9e;font-size:.75rem;font-weight:800;text-align:center;font-variant-numeric:tabular-nums}.boundary-toggle{border-color:#b9cee3;color:#073d82;background:#f4f8fc}
    .leaflet-control-zoom{overflow:hidden!important;border:0!important;border-radius:10px!important;box-shadow:0 5px 18px rgba(5,43,91,.24)!important}.leaflet-control-zoom a{width:34px!important;height:34px!important;border:0!important;color:#073d82!important;font-weight:800!important;line-height:34px!important}.leaflet-control-zoom a:hover{color:#fff!important;background:#1565c0!important}
    .beneficiary-marker{position:relative!important;border:2px solid #fff!important;border-radius:50% 50% 50% 0!important;transform:rotate(-45deg) scale(1.08)!important;transform-origin:center center!important;box-shadow:0 4px 10px rgba(5,43,91,.55)!important}
    .beneficiary-marker::after{content:none!important}
    .beneficiary-marker b{transform:rotate(45deg)!important;font-size:calc(var(--marker-size) * .48)!important}
    .beneficiary-marker.is-pending{background:#fff!important;border-color:var(--marker-color)!important}
    .beneficiary-marker.is-history{opacity:.78!important;border-radius:50% 50% 50% 0!important;transform:rotate(-45deg) scale(1.08)!important}
    .beneficiary-marker.is-history b{transform:rotate(45deg)!important}
    .legend-symbol{width:1.55rem!important;height:1.55rem!important;border-radius:50% 50% 50% 0!important;transform:rotate(-45deg);border:2px solid #fff;box-shadow:0 2px 5px rgba(5,43,91,.3)}
    .legend-symbol::first-letter{display:inline-block;transform:rotate(45deg)}
    .is-full-map{position:relative;width:100vw;height:100vh;background:#f7fbff}.is-full-map .map-shell{height:100vh;border:0;border-radius:0;box-shadow:none}.is-full-map .map-stage,.is-full-map .map-stage #map{height:100vh}.is-full-map .map-legend,.is-full-map .map-corner-label{display:none}.map-return-button{position:absolute;z-index:1000;top:1rem;left:1rem;display:inline-flex;align-items:center;gap:.35rem;border:1px solid #d8e5f2;border-radius:9px;padding:.65rem .9rem;color:#174b7b;background:#fffffff2;box-shadow:0 8px 20px rgba(7,61,130,.2);font-size:.8rem;font-weight:800;text-decoration:none;backdrop-filter:blur(7px)}.map-return-button:hover{color:#fff;background:#1565c0}.is-full-map .leaflet-top{top:1rem}.is-full-map .leaflet-top.leaflet-right{right:1rem}.is-full-map .leaflet-bottom{bottom:1rem}
    @media(max-width:700px){.map-toolbar{min-height:auto;padding:1.1rem}.map-title-icon,.map-live-indicator{display:none}.map-toolbar-actions{margin-left:auto}.map-stage{margin:.65rem;border-width:3px}.map-stage #map{height:590px}.map-corner-label{top:12px;left:12px}.map-legend{right:10px;bottom:12px}.map-filters{margin:0}.map-toolbar h3{font-size:1.1rem}}
    .map-legend .legend-item{display:grid!important;grid-template-columns:1.55rem minmax(0,1fr) 2rem 1rem!important;align-items:center!important;column-gap:.45rem!important;width:100%!important;box-sizing:border-box!important}
    .map-legend .legend-item .legend-symbol{grid-column:1!important;grid-row:1!important}
    .map-legend .legend-item > span:nth-of-type(2){grid-column:2!important;grid-row:1!important;min-width:0!important;overflow:hidden!important;text-overflow:ellipsis!important;white-space:nowrap!important}
    .map-legend .legend-item .legend-count{grid-column:3!important;grid-row:1!important;margin-left:0!important}
    .map-legend .legend-item > i{grid-column:4!important;grid-row:1!important;justify-self:center!important}
    .leaflet-tooltip-pane{z-index:450!important}.leaflet-tooltip.barangay-label{z-index:450!important;pointer-events:none!important;border:1px solid rgba(109,137,168,.5);border-radius:999px;padding:3px 7px;color:#31516f;background:rgba(255,255,255,.7);box-shadow:0 1px 4px rgba(5,43,91,.12);font-size:.62rem;font-weight:700;text-shadow:0 1px #fff}
    .map-stage #map{transform:rotate(var(--map-bearing,0deg));transform-origin:center center;transition:transform .25s ease}
    .map-stage .leaflet-control-container{transform:rotate(calc(var(--map-bearing,0deg) * -1));transform-origin:center center}
    .map-compass{position:absolute;z-index:700;top:16px;right:16px;width:124px;height:124px;border:1px solid rgba(255,255,255,.9);border-radius:50%;color:#164c75;background:rgba(255,255,255,.93);box-shadow:0 10px 24px rgba(5,43,91,.28),inset 0 0 0 5px rgba(22,76,117,.05);font-weight:800;pointer-events:auto}
    .map-compass:before{content:'';position:absolute;inset:11px;border:2px solid #164c75;border-radius:50%;background:repeating-conic-gradient(from 0deg,rgba(22,76,117,.75) 0deg 1deg,transparent 1deg 15deg);-webkit-mask:radial-gradient(farthest-side,transparent calc(100% - 8px),#000 calc(100% - 7px));mask:radial-gradient(farthest-side,transparent calc(100% - 8px),#000 calc(100% - 7px))}
    .map-compass:after{content:'';position:absolute;inset:22px;border:1px solid rgba(22,76,117,.42);border-radius:50%}
    .map-compass span{position:absolute;font-size:.68rem;letter-spacing:.08em;text-shadow:0 1px 0 #fff}.compass-n{top:4px;left:50%;transform:translateX(-50%);color:#0d4168;font-size:.95rem!important}.compass-e{right:6px;top:50%;transform:translateY(-50%)}.compass-s{bottom:4px;left:50%;transform:translateX(-50%)}.compass-w{left:6px;top:50%;transform:translateY(-50%)}
    .compass-rose{position:absolute;z-index:1;inset:31px;display:block;font-size:0;transform:rotate(calc(var(--compass-bearing,0deg) * -1));transition:transform .3s ease}
    .compass-rose:before,.compass-rose:after{content:'';position:absolute;inset:0;clip-path:polygon(50% 0,62% 42%,100% 50%,62% 58%,50% 100%,38% 58%,0 50%,38% 42%);background:linear-gradient(90deg,#2b83b5 0 50%,#0c416a 50% 100%);filter:drop-shadow(0 2px 2px rgba(5,43,91,.3))}
    .compass-rose:after{transform:rotate(45deg) scale(.58);background:linear-gradient(90deg,#4aa1c9 0 50%,#18577f 50% 100%);filter:none}
    .compass-actions{position:absolute;z-index:2;top:calc(100% + 8px);left:50%;display:flex;gap:4px;transform:translateX(-50%)}.compass-actions button{width:30px;height:26px;border:1px solid #b6cfe2;border-radius:7px;color:#164c75;background:#fff;box-shadow:0 2px 5px rgba(5,43,91,.12);font-size:.82rem;font-weight:800;cursor:pointer;transition:color .15s,background .15s,transform .15s}.compass-actions button:hover{color:#fff;background:#1565c0}.compass-actions button:active{transform:translateY(1px)}
    @media(max-width:700px){.map-compass{top:12px;right:12px;transform:scale(.82);transform-origin:top right}}
    </style>
</x-app-layout>
