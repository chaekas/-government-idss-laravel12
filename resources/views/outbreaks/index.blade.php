@extends('layouts.app')
@section('title','Outbreaks')
@section('content')
<div class="heading"><div><small class="kicker">RESPONSE / OUTBREAK MONITORING</small><h1>Disease outbreaks</h1><p>Monitor suspected outbreak events, linked cases, geographic location and response status.</p></div><a class="button primary" href="{{ route('outbreaks.create') }}">＋ Record outbreak</a></div>
<section class="panel"><form method="GET" class="toolbar"><input name="q" value="{{ request('q') }}" placeholder="Search outbreak, disease or district"><select name="status"><option value="">All statuses</option>@foreach(['suspected','active','closed'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select><button class="button secondary">Filter</button><a class="button secondary" href="{{ route('outbreaks.index') }}">Clear</a></form>
<div class="panel-head"><div><h2>Outbreak register</h2><p>{{ $outbreaks->total() }} records</p></div></div>
@if($outbreaks->isEmpty())<div class="empty"><b>No outbreak records</b><p>Outbreak records should be validated by responsible surveillance officers.</p></div>@else
@foreach($outbreaks as $outbreak)<article class="panel" style="margin:12px 0;padding:16px">
<div class="panel-head"><div><h2>{{ $outbreak->outbreak_code }} · {{ $outbreak->disease_name }}</h2><p>{{ $outbreak->district }}, {{ $outbreak->region }} · Started {{ $outbreak->start_date?->format('d M Y') }}</p></div><b>{{ ucfirst($outbreak->status) }}</b></div>
<div class="form-grid"><div><small>Linked cases</small><b>{{ $outbreak->diseaseCases->count() }}</b></div><div><small>Cases / deaths</small><b>{{ $outbreak->cases_count }} / {{ $outbreak->deaths_count }}</b></div><div><small>Severity</small><b>{{ ucfirst($outbreak->severity) }}</b></div><div><small>Confirmation</small><b>{{ $outbreak->confirmed_at?->format('d M Y') ?? 'Not confirmed' }}</b></div></div>
@if($outbreak->diseaseCases->isNotEmpty())<details><summary>View linked case reports</summary><ul>@foreach($outbreak->diseaseCases as $case)<li>{{ $case->case_code }} — {{ $case->disease_suspected }} ({{ $case->status }})</li>@endforeach</ul></details>@endif
<p>{{ $outbreak->response_actions }}</p>
@if($outbreak->status!=='closed' && in_array(auth()->user()->role,['field_officer','regional_veterinary_officer','mlf_officer','system_admin','super_admin','super-admin','administrator','admin'],true))
<details><summary>Update outbreak status and response</summary>
<form method="POST" action="{{ route('outbreaks.update',$outbreak) }}" style="padding:12px">@csrf @method('PUT')
<div class="form-grid">
<div class="field"><label>Disease *</label><input name="disease_name" required value="{{ $outbreak->disease_name }}"></div><div class="field"><label>Classification</label><select name="disease_category_id"><option value="">Not classified</option>@foreach(\App\Models\DiseaseCategory::where('is_active',true)->orderBy('name')->get() as $cat)<option value="{{ $cat->id }}" @selected($outbreak->disease_category_id==$cat->id)>{{ $cat->name }}</option>@endforeach</select></div>
<div class="field"><label>Start date *</label><input type="date" name="start_date" required value="{{ $outbreak->start_date?->toDateString() }}"></div><div class="field"><label>Region *</label><input name="region" required value="{{ $outbreak->region }}"></div><div class="field"><label>District *</label><input name="district" required value="{{ $outbreak->district }}"></div>
<div class="field"><label>Cases *</label><input type="number" min="1" name="cases_count" required value="{{ $outbreak->cases_count }}"></div><div class="field"><label>Deaths *</label><input type="number" min="0" name="deaths_count" required value="{{ $outbreak->deaths_count }}"></div><div class="field"><label>Severity *</label><select name="severity">@foreach(['low','medium','high','critical'] as $s)<option value="{{ $s }}" @selected($outbreak->severity===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
<div class="field"><label>Status *</label><select name="status">@foreach(['suspected','active','closed'] as $s)<option value="{{ $s }}" @selected($outbreak->status===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
<div class="field"><label>Latitude</label><input type="number" step="any" name="latitude" value="{{ $outbreak->latitude }}"></div><div class="field"><label>Longitude</label><input type="number" step="any" name="longitude" value="{{ $outbreak->longitude }}"></div>
<div class="field full"><label>Link cases from this district and region</label><select name="case_ids[]" multiple size="5">@foreach(\App\Models\DiseaseCase::with('farm')->whereHas('farm',fn($q)=>$q->where('district',$outbreak->district)->where('region',$outbreak->region))->get() as $case)<option value="{{ $case->id }}" @selected($outbreak->diseaseCases->contains('id',$case->id))>{{ $case->case_code }} — {{ $case->disease_suspected }}</option>@endforeach</select></div>
<div class="field full"><label>Response actions</label><textarea name="response_actions" rows="3">{{ $outbreak->response_actions }}</textarea></div>
</div><button class="button primary">Save outbreak update</button></form></details>
@endif
</article>@endforeach
<div class="pagination">{{ $outbreaks->links() }}</div>@endif
</section>
@endsection
