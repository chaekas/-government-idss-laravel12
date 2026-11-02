@extends('layouts.app')
@section('title','Poultry registry')
@section('content')
<div class="heading"><div><small class="kicker">TRACEABILITY / ANIMAL MANAGEMENT</small><h1>Poultry and animal registry</h1><p>Maintain identifiable animals linked to registered farms.</p></div><a class="button primary" href="{{ route('animals.create') }}">＋ Register animal</a></div>
<form method="GET" class="panel" style="padding:14px;margin-bottom:16px;display:flex;gap:10px"><input name="q" value="{{ request('q') }}" placeholder="Search Animal ID, species or external ID" style="flex:1;padding:10px;border:1px solid #dce5df;border-radius:7px"><button class="button secondary">Search</button></form>
<section class="panel"><div class="panel-head"><div><h2>Registered animals</h2><p>{{ $animals->total() }} records</p></div></div>
@if($animals->isEmpty())<div class="empty"><b>No animal records</b><p>Register an animal to begin traceability.</p></div>@else<div class="table-wrap"><table><thead><tr><th>Animal ID</th><th>Species</th><th>Breed</th><th>Farm</th><th>External ID</th><th>Status</th></tr></thead><tbody>@foreach($animals as $animal)<tr><td><b>{{ $animal->animal_code }}</b></td><td>{{ $animal->species }}</td><td>{{ $animal->breed ?: '—' }}</td><td>{{ $animal->farm?->name ?? '—' }}</td><td>{{ $animal->external_identifier ?: '—' }}</td><td>{{ ucfirst($animal->ownership_status) }}</td></tr>@endforeach</tbody></table></div><div class="pagination">{{ $animals->links() }}</div>@endif
</section>
@endsection
