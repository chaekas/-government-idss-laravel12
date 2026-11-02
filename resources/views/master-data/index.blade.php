@extends('layouts.app')
@section('title', $definition['title'])
@section('content')
<div class="page-head"><div><span class="eyebrow">SYSTEM ADMINISTRATION</span><h1>{{ $definition['title'] }}</h1><p>Maintain centrally managed IDSS reference data.</p></div><a class="btn primary" href="{{ route('master-data.create', $type) }}">+ Add record</a></div>
<div class="panel">
    <form method="GET" class="toolbar"><input name="q" value="{{ request('q') }}" placeholder="Search code or name"><button class="btn" type="submit">Search</button></form>
    <div class="table-wrap"><table><thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    @forelse($records as $record)
        <tr><td>{{ $record->code }}</td><td>{{ $record->name }}</td><td><span class="status">{{ $record->is_active ? 'Active' : 'Inactive' }}</span></td><td class="actions"><a href="{{ route('master-data.edit', [$type, $record->id]) }}">Edit</a><form method="POST" action="{{ route('master-data.destroy', [$type, $record->id]) }}" onsubmit="return confirm('Archive this reference record?')">@csrf @method('DELETE')<button class="link-danger" type="submit">Archive</button></form></td></tr>
    @empty<tr><td colspan="4">No reference records have been configured.</td></tr>@endforelse
    </tbody></table></div>
    {{ $records->links() }}
</div>
<div class="panel"><h3>Reference data catalogues</h3><div class="catalog-links">
@foreach(['farm-types'=>'Farm types','species'=>'Species','breeds'=>'Breeds','disease-categories'=>'Disease categories','disease-symptoms'=>'Disease symptoms','sample-types'=>'Sample types','laboratory-tests'=>'Laboratory tests','test-methods'=>'Test methods','administrative-areas'=>'Administrative areas','surveillance-alert-rules'=>'Surveillance alert rules'] as $key=>$label)
<a href="{{ route('master-data.index',$key) }}" class="{{ $type === $key ? 'selected' : '' }}">{{ $label }}</a>
@endforeach
</div></div>
@endsection
