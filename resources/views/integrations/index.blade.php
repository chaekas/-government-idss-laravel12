@extends('layouts.app')
@section('title','Government integrations')
@section('content')
<div class="heading"><div><small class="kicker">SYSTEM / EXTERNAL CONNECTIVITY</small><h1>Government integrations</h1><p>Monitor approved connector configuration, outbound delivery queue and provider audit history.</p></div></div>
<section class="panel">
    <div class="panel-head"><div><h2>Provider configuration</h2><p>All external delivery is disabled by default until Ministry API specifications and credentials are approved.</p></div></div>
    <div class="stats">
        @foreach($providers as $key => $provider)
            <article><span>{{ strtoupper($key) }}</span><strong>{{ ($provider['enabled'] ?? false) ? 'Enabled' : 'Disabled' }}</strong><small>{{ $provider['adapter'] ?: 'No adapter configured' }}</small></article>
        @endforeach
    </div>
    <p><b>Important:</b> Do not enable a provider or add credentials until the system owner approves the data-sharing agreement, API contract, security controls and test endpoint.</p>
</section>
<section class="panel">
    <div class="panel-head"><div><h2>Outbound delivery queue</h2><p>Queued messages are processed only when an approved provider adapter is enabled.</p></div>
        <form method="POST" action="{{ route('integrations.process') }}">@csrf<button class="button primary">Process enabled integrations</button></form>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Provider / channel</th><th>Event</th><th>Recipient</th><th>Status</th><th>Attempts</th><th>Available</th><th>Last error</th></tr></thead><tbody>
    @forelse($outbox as $item)<tr><td>{{ strtoupper($item->provider) }} / {{ $item->channel }}</td><td>{{ $item->event_type }}</td><td>{{ $item->recipient ?: '—' }}</td><td>{{ ucfirst($item->status) }}</td><td>{{ $item->attempts }}/{{ $item->max_attempts }}</td><td>{{ $item->available_at?->format('d M Y H:i') }}</td><td>{{ $item->last_error ?: '—' }}</td></tr>@empty<tr><td colspan="7">No outbound integration events have been queued.</td></tr>@endforelse
    </tbody></table></div>{{ $outbox->links() }}
</section>
<section class="panel">
    <h2>Integration audit log</h2>
    <div class="table-wrap"><table><thead><tr><th>Occurred</th><th>Provider</th><th>Operation</th><th>Status</th><th>External reference</th><th>Error</th></tr></thead><tbody>
    @forelse($logs as $log)<tr><td>{{ $log->occurred_at?->format('d M Y H:i:s') }}</td><td>{{ strtoupper($log->provider) }}</td><td>{{ $log->operation }}</td><td>{{ ucfirst($log->status) }}</td><td>{{ $log->external_reference ?: '—' }}</td><td>{{ $log->error_message ?: '—' }}</td></tr>@empty<tr><td colspan="6">No integration attempts recorded.</td></tr>@endforelse
    </tbody></table></div>{{ $logs->links() }}
</section>
@endsection
