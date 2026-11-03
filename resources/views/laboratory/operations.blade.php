@extends('layouts.app')
@section('title','Laboratory operations')
@section('content')
<div class="heading"><div><small class="kicker">LABORATORY / QUALITY MANAGEMENT</small><h1>Laboratory operations and quality assurance</h1><p>Manage laboratory sections, diagnostic equipment, calibration history, QC inspections and corrective actions.</p></div></div>

<section class="panel form-panel">
    <h2>Register laboratory section</h2>
    <form method="POST" action="{{ route('laboratory.sections.store') }}" class="form-grid">@csrf
        <div class="field"><label>Laboratory *</label><select name="laboratory_id" required><option value="">Select laboratory</option>@foreach($laboratories as $lab)<option value="{{ $lab->id }}">{{ $lab->name }}</option>@endforeach</select></div>
        <div class="field"><label>Section code *</label><input name="code" required placeholder="e.g. VIROLOGY"></div>
        <div class="field"><label>Section name *</label><input name="name" required placeholder="Virology"></div>
        <div class="field"><button class="button primary">Add section</button></div>
    </form>
</section>

<section class="panel form-panel">
    <h2>Register diagnostic equipment</h2>
    <form method="POST" action="{{ route('laboratory.equipment.store') }}" class="form-grid">@csrf
        <div class="field"><label>Laboratory *</label><select name="laboratory_id" required><option value="">Select laboratory</option>@foreach($laboratories as $lab)<option value="{{ $lab->id }}">{{ $lab->name }}</option>@endforeach</select></div>
        <div class="field"><label>Equipment code *</label><input name="equipment_code" required></div>
        <div class="field"><label>Equipment name *</label><input name="name" required></div>
        <div class="field"><label>Manufacturer</label><input name="manufacturer"></div>
        <div class="field"><label>Model</label><input name="model"></div>
        <div class="field"><label>Serial number</label><input name="serial_number"></div>
        <div class="field"><label>Last calibrated</label><input type="date" name="last_calibrated_at" max="{{ now()->toDateString() }}"></div>
        <div class="field"><label>Calibration due</label><input type="date" name="calibration_due_at"></div>
        <div class="field"><label>Initial status *</label><select name="status"><option value="active">Active</option><option value="maintenance">Maintenance</option><option value="retired">Retired</option></select></div>
        <div class="field"><button class="button primary">Register equipment</button></div>
    </form>
</section>

@foreach($laboratories as $lab)
<section class="panel">
    <div class="panel-head"><div><h2>{{ $lab->name }}</h2><p>{{ $lab->code }} · {{ ucfirst($lab->level) }} laboratory</p></div></div>
    <h3>Laboratory sections</h3>
    <div class="catalog-links">@forelse($lab->sections as $section)<span class="status">{{ $section->code }} — {{ $section->name }}</span>@empty<small>No sections registered yet.</small>@endforelse</div>
    <h3 class="section-title">Equipment and calibration history</h3>
    @forelse($lab->equipment as $equipment)
        <div class="panel" style="margin:10px 0;padding:14px">
            <div class="panel-head"><div><b>{{ $equipment->equipment_code }} · {{ $equipment->name }}</b><p>{{ $equipment->manufacturer }} {{ $equipment->model }} · Serial: {{ $equipment->serial_number ?: '—' }}</p><small>Status: {{ ucfirst($equipment->status) }} · Last calibration: {{ $equipment->last_calibrated_at?->format('d M Y') ?: 'Not recorded' }} · Due: {{ $equipment->calibration_due_at?->format('d M Y') ?: 'Not recorded' }}</small></div>
                <form method="POST" action="{{ route('laboratory.equipment.update',$equipment) }}">@csrf @method('PUT')<select name="status"><option value="active" @selected($equipment->status==='active')>Active</option><option value="maintenance" @selected($equipment->status==='maintenance')>Maintenance</option><option value="retired" @selected($equipment->status==='retired')>Retired</option></select><button class="button secondary">Update status</button></form>
            </div>
            <details><summary>Record calibration</summary>
                <form method="POST" action="{{ route('laboratory.equipment.calibrations.store',$equipment) }}" class="form-grid" style="padding:10px">@csrf
                    <div class="field"><label>Calibration date *</label><input type="date" name="calibration_date" max="{{ now()->toDateString() }}" required></div>
                    <div class="field"><label>Next due date</label><input type="date" name="due_date"></div>
                    <div class="field"><label>Outcome *</label><select name="outcome"><option value="passed">Passed</option><option value="conditional">Conditional</option><option value="failed">Failed</option></select></div>
                    <div class="field"><label>Certificate reference</label><input name="certificate_reference"></div>
                    <div class="field full"><label>Findings</label><textarea name="findings" rows="2"></textarea></div>
                    <div class="field full"><label>Corrective action (required if failed)</label><textarea name="corrective_action" rows="2"></textarea></div>
                    <div class="field"><button class="button primary">Save calibration</button></div>
                </form>
            </details>
            <details><summary>Calibration records ({{ $equipment->calibrations->count() }})</summary>
                @foreach($equipment->calibrations as $calibration)
                    <p><b>{{ $calibration->calibration_date->format('d M Y') }}</b> · {{ ucfirst($calibration->outcome) }} · Due {{ $calibration->due_date?->format('d M Y') ?: '—' }}<br><small>{{ $calibration->certificate_reference }} · {{ $calibration->findings }}</small></p>
                @endforeach
            </details>
        </div>
    @empty
        <p>No equipment registered for this laboratory.</p>
    @endforelse
</section>
@endforeach

<section class="panel form-panel">
    <h2>Record quality-control inspection</h2>
    <form method="POST" action="{{ route('laboratory.quality-control.store') }}" class="form-grid">@csrf
        <div class="field"><label>Laboratory *</label><select name="laboratory_id" required><option value="">Select laboratory</option>@foreach($laboratories as $lab)<option value="{{ $lab->id }}">{{ $lab->name }}</option>@endforeach</select></div>
        <div class="field"><label>QC type *</label><select name="qc_type" required><option value="sample_reception">Sample reception</option><option value="assay_run">Assay / test run</option><option value="control_material">Control material</option><option value="environmental">Environmental monitoring</option><option value="internal_audit">Internal quality audit</option></select></div>
        <div class="field"><label>Related sample</label><select name="sample_id"><option value="">Not sample-specific</option>@foreach($samples as $sample)<option value="{{ $sample->id }}">{{ $sample->sample_code }} · {{ $sample->laboratory?->name }}</option>@endforeach</select></div>
        <div class="field"><label>Diagnostic test</label><select name="laboratory_test_id"><option value="">Not test-specific</option>@foreach($tests as $test)<option value="{{ $test->id }}">{{ $test->code }} — {{ $test->name }}</option>@endforeach</select></div>
        <div class="field"><label>Outcome *</label><select name="outcome"><option value="passed">Passed</option><option value="conditional">Conditional</option><option value="failed">Failed</option></select></div>
        <div class="field"><label>Performed at *</label><input type="datetime-local" name="performed_at" max="{{ now()->format('Y-m-d\TH:i') }}" required></div>
        <div class="field full"><label>Findings *</label><textarea name="findings" rows="3" required></textarea></div>
        <div class="field full"><label>Corrective action (required if failed)</label><textarea name="corrective_action" rows="3"></textarea></div>
        <div class="field"><button class="button primary">Save QC inspection</button></div>
    </form>
</section>

<section class="panel">
    <h2>Quality-control history</h2>
    <div class="table-wrap"><table><thead><tr><th>Date</th><th>Laboratory</th><th>Type / related record</th><th>Outcome</th><th>Findings</th></tr></thead><tbody>
    @forelse($qualityRecords as $record)<tr><td>{{ $record->performed_at?->format('d M Y H:i') }}</td><td>{{ $record->laboratory?->name }}</td><td>{{ str_replace('_',' ',ucfirst($record->qc_type)) }}<br><small>{{ $record->sample?->sample_code ?: $record->test?->name ?: 'General QC' }}</small></td><td>{{ ucfirst($record->outcome) }}</td><td>{{ $record->findings }}</td></tr>@empty<tr><td colspan="5">No quality inspections recorded.</td></tr>@endforelse
    </tbody></table></div>{{ $qualityRecords->links() }}
</section>

<section class="panel">
    <h2>Corrective and preventive actions</h2>
    <div class="table-wrap"><table><thead><tr><th>Action</th><th>Laboratory / priority</th><th>Description</th><th>Status / resolution</th></tr></thead><tbody>
    @forelse($correctiveActions as $action)
        <tr><td><b>{{ $action->action_code }}</b><br><small>Due: {{ $action->due_date?->format('d M Y') ?: 'Not set' }}</small></td><td>{{ $action->laboratory?->name }}<br>{{ ucfirst($action->priority) }}</td><td>{{ $action->description }}</td><td>
            <form method="POST" action="{{ route('laboratory.corrective-actions.update',$action) }}">@csrf @method('PUT')
                <select name="status"><option value="open" @selected($action->status==='open')>Open</option><option value="in_progress" @selected($action->status==='in_progress')>In progress</option><option value="resolved" @selected($action->status==='resolved')>Resolved</option><option value="cancelled" @selected($action->status==='cancelled')>Cancelled</option></select>
                <input type="date" name="due_date" value="{{ $action->due_date?->format('Y-m-d') }}">
                <textarea name="resolution" rows="2" placeholder="Resolution (required when resolved)">{{ $action->resolution }}</textarea>
                <button class="button secondary">Update action</button>
            </form>
        </td></tr>
    @empty<tr><td colspan="4">No corrective actions are open.</td></tr>@endforelse
    </tbody></table></div>{{ $correctiveActions->links() }}
</section>
@endsection
