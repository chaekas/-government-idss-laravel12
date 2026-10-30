@extends('layouts.app')
@section('title','Farms')
@section('content')
<div class="heading"><div><small class="kicker">REGISTRY / FARM MANAGEMENT</small><h1>Farm registry</h1><p>Maintain poultry farm locations and farmer associations.</p></div><a class="button primary" href="{{ route('farms.create') }}">＋ Register farm</a></div>
<section class="panel"><div class="panel-head"><div><h2>Registered farms</h2><p>{{ $farms->total() }} records</p></div></div>@if($farms->isEmpty())<div class="empty"><b>No farms registered</b><p>Register a farmer before registering a farm.</p><a class="button primary" href="{{ route('farms.create') }}">Register farm</a></div>@else<div class="table-wrap"><table><thead><tr><th>Farm code</th><th>Farm name</th><th>Farmer</th><th>Region</th><th>District</th><th>Bird count</th></tr></thead><tbody>@foreach($farms as $farm)<tr><td>{{ $farm->farm_code }}</td><td><b>{{ $farm->name }}</b></td><td>{{ $farm->farmer?->full_name ?? '—' }}</td><td>{{ $farm->region }}</td><td>{{ $farm->district }}</td><td>{{ number_format($farm->bird_count) }}</td></tr>@endforeach</tbody></table></div><div class="pagination">{{ $farms->links() }}</div>@endif</section>
@endsection
