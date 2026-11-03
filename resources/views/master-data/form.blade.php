@extends('layouts.app')
@section('title', isset($item) ? 'Edit '.$definition['title'] : 'Add '.$definition['title'])
@section('content')
<div class="page-head"><div><span class="eyebrow">SYSTEM ADMINISTRATION</span><h1>{{ isset($item) ? 'Edit' : 'Add' }} {{ $definition['title'] }}</h1><p>Maintain approved organization, office, geographic and reference records.</p></div><a class="btn" href="{{ route('master-data.index',$type) }}">Back to list</a></div>
<div class="panel form-panel"><form method="POST" action="{{ isset($item) ? route('master-data.update',[$type,$item->id]) : route('master-data.store',$type) }}">
@csrf @if(isset($item)) @method('PUT') @endif
@foreach($definition['fields'] as $field)
    @php($value = old($field, $item->$field ?? (in_array($field,['is_active']) ? 1 : '')))
    <label class="field"><span>{{ ucwords(str_replace('_',' ',$field)) }} @if(in_array($field,['code','name','area_type','species_id','organization_id'])) <b>*</b> @endif</span>
    @if($field === 'description' || $field === 'specimen_requirements')
        <textarea name="{{ $field }}" rows="3">{{ $value }}</textarea>
    @elseif($field === 'is_active')
        <select name="is_active"><option value="1" @selected((string)$value === '1')>Active</option><option value="0" @selected((string)$value === '0')>Inactive</option></select>
    @elseif($field === 'organization_id')
        <select name="organization_id"><option value="">Not assigned</option>@foreach($options['organizations'] as $option)<option value="{{ $option->id }}" @selected((string)$value === (string)$option->id)>{{ $option->name }}</option>@endforeach</select>
    @elseif($field === 'department_id')
        <select name="department_id"><option value="">Not assigned</option>@foreach($options['departments'] as $option)<option value="{{ $option->id }}" @selected((string)$value === (string)$option->id)>{{ $option->name }}</option>@endforeach</select>
    @elseif($field === 'administrative_area_id')
        <select name="administrative_area_id"><option value="">Not assigned</option>@foreach($options['administrativeAreas'] as $option)<option value="{{ $option->id }}" @selected((string)$value === (string)$option->id)>{{ $option->name }} ({{ $option->area_type }})</option>@endforeach</select>
    @elseif($field === 'species_id')
        <select name="species_id" required><option value="">Select species</option>@foreach($options['species'] as $option)<option value="{{ $option->id }}" @selected((string)$value === (string)$option->id)>{{ $option->name }}</option>@endforeach</select>
    @elseif($field === 'disease_category_id')
        <select name="disease_category_id"><option value="">Not specified</option>@foreach($options['diseaseCategories'] as $option)<option value="{{ $option->id }}" @selected((string)$value === (string)$option->id)>{{ $option->name }}</option>@endforeach</select>
    @elseif($field === 'parent_id')
        <select name="parent_id"><option value="">No parent (top level)</option>@foreach($options['administrativeAreas'] as $option)<option value="{{ $option->id }}" @selected((string)$value === (string)$option->id)>{{ $option->name }} ({{ $option->area_type }})</option>@endforeach</select>
    @elseif($field === 'area_type')
        <select name="area_type" required>@foreach(['region','district','ward','village'] as $option)<option value="{{ $option }}" @selected($value === $option)>{{ ucfirst($option) }}</option>@endforeach</select>
    @elseif(in_array($field,['notify_field_officers','notify_regional_officers','notify_ministry_officers']))
        <select name="{{ $field }}"><option value="1" @selected((string)$value === '1')>Yes</option><option value="0" @selected((string)$value === '0')>No</option></select>
    @elseif($field === 'severity')
        <select name="severity">@foreach(['low','medium','high','critical'] as $option)<option value="{{ $option }}" @selected($value === $option)>{{ ucfirst($option) }}</option>@endforeach</select>
    @elseif(in_array($field,['case_threshold','within_hours']))
        <input type="number" min="1" name="{{ $field }}" value="{{ $value }}" required>
    @elseif($field === 'sort_order')
        <input type="number" min="0" name="{{ $field }}" value="{{ $value }}">
    @else
        <input name="{{ $field }}" value="{{ $value }}" @if(in_array($field,['code','name'])) required @endif>
    @endif
    </label>
@endforeach
<div class="form-actions"><button class="btn primary" type="submit">Save record</button><a class="btn" href="{{ route('master-data.index',$type) }}">Cancel</a></div>
</form></div>
@endsection
