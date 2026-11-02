@extends('layouts.app')
@section('title','Outbreaks')
@section('content')
<div class="heading"><div><small class="kicker">RESPONSE / OUTBREAK MONITORING</small><h1>Disease outbreaks</h1><p>Record suspected outbreak events and response actions for review.</p></div><a class="button primary" href="{{ route('outbreaks.create') }}">＋ Record outbreak</a></div>
<section class="panel"><div class="panel-head"><div><h2>Outbreak register</h2><p>{{ $outbreaks->total() }} records</p></div></div>
@if($outbreaks->isEmpty())<div class="empty"><b>No outbreak records</b><p>Outbreak records should be validated by the responsible surveillance officers.</p></div>@else<div class="table-wrap"><table><thead><tr><th>Outbreak ID</th><th>Disease</th><th>Location</th><th>Start date</th><th>Cases / deaths</th><th>Severity</th><th>Status</th></tr></thead><tbody>@foreach($outbreaks as $outbreak)<tr><td><b>{{ $outbreak->outbreak_code }}</b></td><td>{{ $outbreak->disease_name }}</td><td>{{ $outbreak->district }}, {{ $outbreak->region }}</td><td>{{ $outbreak->start_date?->format('d M Y') }}</td><td>{{ $outbreak->cases_count }} / {{ $outbreak->deaths_count }}</td><td><span class="badge {{ $outbreak->severity }}">{{ ucfirst($outbreak->severity) }}</span></td><td>{{ ucfirst($outbreak->status) }}</td></tr>@endforeach</tbody></table></div><div class="pagination">{{ $outbreaks->links() }}</div>@endif
</section>
@endsection
