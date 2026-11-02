@extends('layouts.app')
@section('title','Samples')
@section('content')
@php
    $role = auth()->user()->role;
    $adminRoles = ['system_admin', 'administrator', 'admin'];
    $labRoles = ['laboratory_technician', 'laboratory_manager', 'zonal_laboratory_administrator'];
    $canRegister = in_array($role, ['field_officer', ...$adminRoles], true);
    $canAssign = in_array($role, ['laboratory_manager', 'zonal_laboratory_administrator', ...$adminRoles], true);
@endphp
<div class="heading">
    <div>
        <small class="kicker">FIELD → LABORATORY → SURVEILLANCE</small>
        <h1>Sample lifecycle and chain of custody</h1>
        <p>Follow each sample from field collection, packaging and transport to laboratory QC, testing, approval and final result release.</p>
    </div>
    @if($canRegister)<a class="button primary" href="{{ route('samples.create') }}">＋ Register sample</a>@endif
</div>

<section class="panel">
    <div class="panel-head"><div><h2>Sample register</h2><p>{{ $samples->total() }} records · every confirmed handoff is retained in the audit trail</p></div></div>
    @if($samples->isEmpty())
        <div class="empty"><b>No samples in your workflow</b><p>Samples appear here after a field officer registers them. Laboratory technicians see samples assigned to them.</p></div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>Sample / case</th><th>Collection & destination</th><th>Lifecycle status</th><th>Assignment / final result</th><th>Next action</th></tr></thead>
            <tbody>
            @foreach($samples as $sample)
                <tr>
                    <td>
                        <b>{{ $sample->sample_code }}</b><br><small>Barcode: {{ $sample->barcode_value }}</small><br>
                        <small>Case: {{ $sample->diseaseCase?->case_code }}</small><br>
                        <small>Collected by: {{ $sample->collector?->name ?: 'Unknown' }} · {{ $sample->collection_date?->format('d M Y') }}</small>
                    </td>
                    <td>
                        <b>{{ $sample->sample_type }}</b><br>
                        {{ $sample->laboratory?->name ?: 'No destination lab' }}<br>
                        @if($sample->laboratory_reference_number)<small>Lab ref: {{ $sample->laboratory_reference_number }}</small><br>@endif
                        <small>Received: {{ $sample->received_at?->format('d M Y H:i') ?: 'Pending' }}</small>
                    </td>
                    <td>
                        <b>Transport:</b> {{ str_replace('_',' ',ucfirst($sample->transport_status)) }}<br>
                        <b>Quality:</b> {{ str_replace('_',' ',ucfirst($sample->quality_status)) }}<br>
                        <b>Testing:</b> {{ str_replace('_',' ',ucfirst($sample->test_status)) }}<br>
                        @if($sample->rejection_reason)<small>Reason: {{ $sample->rejection_reason }}</small>@endif
                    </td>
                    <td>
                        @if($sample->assignedTechnician)
                            <b>{{ $sample->assignedTechnician->name }}</b><br>
                            <small>{{ $sample->laboratory_section ?: 'Laboratory section not set' }}</small><br>
                            <small>Assigned: {{ $sample->assigned_at?->format('d M Y H:i') }}</small>
                        @else
                            <small>Awaiting laboratory assignment</small>
                        @endif
                        <hr>
                        @forelse($sample->results as $result)
                            <small><b>{{ $result->result_code }}</b> · {{ $result->test_name }} · {{ str_replace('_',' ',ucfirst($result->status)) }}</small><br>
                            @if($result->status === 'approved')
                                <small>Final finding: {{ $result->qualitative_result ?: ($result->numeric_result !== null ? $result->numeric_result.' '.$result->numeric_unit : $result->observations) }}</small><br>
                            @endif
                        @empty
                            <small>No test results yet.</small>
                        @endforelse
                    </td>
                    <td style="min-width:260px">
                        @if(in_array($role, ['field_officer','regional_veterinary_officer', ...$adminRoles], true) && $sample->transport_status === 'not_dispatched')
                            <details><summary>Package & dispatch</summary>
                                <form method="POST" action="{{ route('samples.update',$sample) }}" style="min-width:220px;padding:8px">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="event_type" value="dispatched">
                                    <label>From</label><input value="Field collection" disabled>
                                    <label>To</label><input name="to_location" value="{{ $sample->laboratory?->name }}" required>
                                    <label>Packaging / transport condition</label><input name="condition_on_transfer" required>
                                    <label>Dispatch notes</label><textarea name="notes" rows="2"></textarea>
                                    <div class="actions"><button class="button primary">Confirm dispatch</button></div>
                                </form>
                            </details>
                        @endif

                        @if((in_array($role, $labRoles, true) || in_array($role, $adminRoles, true)) && $sample->transport_status === 'in_transit')
                            <details><summary>Receive at laboratory</summary>
                                <form method="POST" action="{{ route('samples.update',$sample) }}" style="min-width:220px;padding:8px">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="event_type" value="received">
                                    <label>Received by *</label><input name="received_by" required>
                                    <label>Receiving location</label><input name="to_location" value="{{ $sample->laboratory?->name }}">
                                    <label>Condition on arrival *</label><input name="condition_on_transfer" required>
                                    <label>Reception notes</label><textarea name="notes" rows="2"></textarea>
                                    <div class="actions"><button class="button primary">Confirm receipt</button></div>
                                </form>
                            </details>
                        @endif

                        @if((in_array($role, $labRoles, true) || in_array($role, $adminRoles, true)) && $sample->transport_status === 'received' && $sample->quality_status === 'pending')
                            <details><summary>Quality control decision</summary>
                                <form method="POST" action="{{ route('samples.update',$sample) }}" style="min-width:220px;padding:8px">
                                    @csrf @method('PUT')
                                    <label>Decision *</label>
                                    <select name="event_type" required>
                                        <option value="accepted">Accept for testing</option><option value="rejected">Reject</option><option value="damaged">Damaged</option><option value="contaminated">Contaminated</option><option value="insufficient_volume">Insufficient volume</option><option value="recollection_required">Recollection required</option>
                                    </select>
                                    <label>Reason (required for rejection)</label><input name="rejection_reason">
                                    <label>QC notes</label><textarea name="notes" rows="2"></textarea>
                                    <div class="actions"><button class="button primary">Record QC decision</button></div>
                                </form>
                            </details>
                        @endif

                        @if($canAssign && $sample->transport_status === 'received' && $sample->quality_status === 'accepted' && $sample->test_status !== 'approved')
                            <details><summary>Assign section & technician</summary>
                                <form method="POST" action="{{ route('samples.assign',$sample) }}" style="min-width:220px;padding:8px">
                                    @csrf @method('PUT')
                                    <label>Laboratory section *</label><input name="laboratory_section" value="{{ $sample->laboratory_section }}" placeholder="e.g. Virology / Bacteriology" required>
                                    <label>Assigned technician *</label>
                                    <select name="assigned_technician" required><option value="">Select active technician</option>
                                        @foreach(\App\Models\User::where('role','laboratory_technician')->where('is_active',true)->orderBy('name')->get() as $technician)
                                            <option value="{{ $technician->id }}" @selected($sample->assigned_technician === $technician->id)>{{ $technician->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="actions"><button class="button primary">Assign sample</button></div>
                                </form>
                            </details>
                        @endif

                        <details style="margin-top:8px"><summary>Chain-of-custody timeline ({{ $sample->movements->count() }})</summary>
                            <div style="padding:8px;min-width:240px">
                                @forelse($sample->movements as $movement)
                                    <p style="margin:0 0 10px"><b>{{ str_replace('_',' ',ucfirst($movement->event_type)) }}</b><br>
                                    <small>{{ $movement->event_at?->format('d M Y H:i') }} · {{ $movement->recordedBy?->name ?: 'System' }}</small><br>
                                    <small>{{ $movement->from_location ?: '—' }} → {{ $movement->to_location ?: '—' }}</small><br>
                                    @if($movement->received_by)<small>Received by: {{ $movement->received_by }}</small><br>@endif
                                    @if($movement->condition_on_transfer)<small>Condition: {{ $movement->condition_on_transfer }}</small><br>@endif
                                    @if($movement->notes)<small>{{ $movement->notes }}</small>@endif
                                    </p>
                                @empty
                                    <small>No movement history recorded.</small>
                                @endforelse
                            </div>
                        </details>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        <div class="pagination">{{ $samples->links() }}</div>
    @endif
</section>
@endsection
