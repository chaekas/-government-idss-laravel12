@extends('layouts.app')
@section('title','Laboratory registry')
@section('content')
<div class="heading"><div><small class="kicker">LABORATORY / FACILITY MANAGEMENT</small><h1>Laboratory registry</h1><p>Maintain registered laboratories used for sample routing and diagnostics.</p></div><a class="button primary" href="{{ route('laboratories.create') }}">＋ Register laboratory</a></div>
<section class="panel"><div class="panel-head"><div><h2>Registered laboratories</h2><p>{{ $laboratories->total() }} records</p></div></div>
@if($laboratories->isEmpty())<div class="empty"><b>No laboratories registered</b><p>Register a laboratory before assigning samples for testing.</p></div>@else<div class="table-wrap"><table><thead><tr><th>Code</th><th>Name</th><th>Level</th><th>Location</th><th>Contact</th><th>Status</th></tr></thead><tbody>@foreach($laboratories as $lab)<tr><td><b>{{ $lab->code }}</b></td><td>{{ $lab->name }}</td><td>{{ ucfirst($lab->level) }}</td><td>{{ collect([$lab->district,$lab->region])->filter()->join(', ') ?: '—' }}</td><td>{{ $lab->contact_email ?: $lab->contact_phone ?: '—' }}</td><td>{{ $lab->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach</tbody></table></div><div class="pagination">{{ $laboratories->links() }}</div>@endif
</section>
@endsection
