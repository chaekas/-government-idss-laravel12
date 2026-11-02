@extends('layouts.app')
@section('title','GIS surveillance map')
@section('content')
<div class="heading"><div><small class="kicker">GEOGRAPHIC SURVEILLANCE / GIS</small><h1>Disease distribution map</h1><p>Explore registered farms, georeferenced disease reports, and outbreak records.</p></div><a class="button secondary" href="{{ route('reports.index') }}">← Reports</a></div>
<div class="notice"><b>i</b><div><strong>Coordinate coverage</strong><p>Only records with saved latitude and longitude appear. Case markers use the recorded case GPS coordinates, falling back to the linked farm coordinates. Filters apply to case and outbreak markers; farm markers remain visible as location references. Map tiles are provided by OpenStreetMap; verify official administrative boundaries and outbreak status before operational decisions.</p></div></div>
<form method="GET" class="panel toolbar">
<label>Disease category <select name="disease_category_id"><option value="">All diseases</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(($filters['disease_category_id'] ?? '')==$category->id)>{{ $category->name }}</option>@endforeach</select></label>
<label>Species <select name="species"><option value="">All species</option>@foreach($speciesOptions as $species)<option value="{{ $species }}" @selected(($filters['species'] ?? '')===$species)>{{ $species }}</option>@endforeach</select></label>
<label>From <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label><label>To <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
<label>Administrative area <select name="administrative_area_id"><option value="">All areas</option>@foreach($areas as $area)<option value="{{ $area->id }}" @selected(($filters['administrative_area_id'] ?? '')==$area->id)>{{ $area->name }}</option>@endforeach</select></label>
<button class="button primary">Filter map</button><a class="button secondary" href="{{ route('gis.index') }}">Clear</a>
</form>
<section class="panel"><div class="panel-head"><div><h2>Map layers</h2><p><span class="badge">Farm</span> <span class="badge high">Disease case</span> <span class="badge critical">Outbreak</span> · {{ count($mapPoints) }} mapped records</p></div></div><div id="idss-map" style="height:560px;min-height:360px;border-radius:12px;z-index:1"></div><div id="map-empty" class="empty" hidden><b>No mapped records yet</b><p>Add GPS coordinates to a farm or outbreak record to display it here.</p></div></section>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const points = @json($mapPoints);
    const map = L.map('idss-map').setView([-6.2, 35.7], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    const bounds = [];
    points.forEach(function (point) {
        const position = [point.latitude, point.longitude];
        const markerColor = point.type === 'outbreak' ? '#b91c1c' : (point.type === 'case' ? '#d97706' : '#047857');
        const marker = L.circleMarker(position, {radius: 8, color: markerColor, fillColor: markerColor, fillOpacity: 0.8, weight: 2}).addTo(map);
        marker.bindPopup('<strong>' + escapeHtml(point.label) + '</strong><br>' + escapeHtml(point.code) + '<br>' + escapeHtml(point.detail));
        bounds.push(position);
    });
    if (bounds.length) {
        map.fitBounds(bounds, {padding: [24, 24], maxZoom: 12});
    } else {
        document.getElementById('map-empty').hidden = false;
    }
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (character) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character];
        });
    }
});
</script>
@endsection
