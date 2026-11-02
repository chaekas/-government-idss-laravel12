@extends('layouts.app')
@section('title','Audit trail')
@section('content')
<div class="heading"><div><small class="kicker">ADMINISTRATION / ACCOUNTABILITY</small><h1>Audit trail</h1><p>Review recorded system actions for traceability and investigation.</p></div></div>
<section class="panel"><div class="panel-head"><div><h2>Recent actions</h2><p>{{ $logs->total() }} records</p></div></div>
@if($logs->isEmpty())<div class="empty"><b>No audit events recorded</b><p>Events from monitored workflows will appear here.</p></div>@else<div class="table-wrap"><table><thead><tr><th>Timestamp</th><th>User</th><th>Action</th><th>Record</th><th>Description</th><th>IP address</th></tr></thead><tbody>@foreach($logs as $log)<tr><td>{{ $log->created_at?->format('d M Y H:i:s') }}</td><td>{{ $log->user?->email ?: 'System / deleted user' }}</td><td>{{ $log->action }}</td><td>{{ $log->entity_type }} #{{ $log->entity_id }}</td><td>{{ $log->description }}</td><td>{{ $log->ip_address ?: '—' }}</td></tr>@endforeach</tbody></table></div><div class="pagination">{{ $logs->links() }}</div>@endif
</section>
@endsection
