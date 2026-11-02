@extends('layouts.app')
@section('title','Laboratory')
@section('content')
@php
    $role = auth()->user()->role;
    $canReview = in_array($role, ['laboratory_manager', 'system_admin', 'administrator', 'admin'], true);
@endphp
<div class="heading">
    <div><small class="kicker">DIAGNOSTICS / LABORATORY WORKFLOW</small><h1>Laboratory testing and result approval</h1><p>Assigned technician records tests → independent laboratory manager reviews → final result is released to the case and surveillance teams.</p></div>
    @if($role === 'laboratory_technician')<a class="button primary" href="{{ route('laboratory.create') }}">＋ Record assigned test</a>@endif
</div>
<section class="panel">
    <div class="panel-head"><div><h2>Test results</h2><p>{{ $results->total() }} records · only manager-approved results become official</p></div></div>
    @if($results->isEmpty())
        <div class="empty"><b>No laboratory tests recorded</b><p>Samples must be received, accepted by QC, and assigned to the signed-in technician before testing.</p></div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>Result ID / sample</th><th>Laboratory / section</th><th>Test & finding</th><th>Workflow status</th><th>Review / final release</th></tr></thead>
            <tbody>
            @foreach($results as $result)
                <tr>
                    <td><b>{{ $result->result_code }}</b><br><small>Sample: {{ $result->sample?->sample_code }}</small><br><small>Case: {{ $result->sample?->diseaseCase?->case_code }}</small><br><small>Technician: {{ $result->sample?->assignedTechnician?->name ?: 'Assigned technician' }}</small></td>
                    <td>{{ $result->laboratory?->name }}<br><small>{{ $result->sample?->laboratory_section ?: 'Section not recorded' }}</small></td>
                    <td><b>{{ $result->test_name }}</b><br>{{ $result->qualitative_result ?: ($result->numeric_result !== null ? $result->numeric_result.' '.$result->numeric_unit : ($result->observations ?: 'Finding not recorded')) }}<br>@if($result->method)<small>Method: {{ $result->method }}</small>@endif</td>
                    <td>{{ str_replace('_',' ',ucfirst($result->status)) }}<br><small>Tested: {{ $result->tested_at?->format('d M Y H:i') }}</small>
                        @if($result->sample?->test_status === 'approved')<br><b>Sample final: Released</b><br><small>{{ $result->sample?->released_at?->format('d M Y H:i') }}</small>@endif
                    </td>
                    <td>
                        @if($result->status === 'submitted_for_review' && $canReview)
                            <details><summary>Review result</summary>
                                <form method="POST" action="{{ route('laboratory.approve',$result) }}" style="min-width:220px;padding:8px">
                                    @csrf @method('PUT')
                                    <label>Decision *</label><select name="decision" required><option value="approve">Approve result</option><option value="reject">Return for correction / repeat</option></select>
                                    <label>Review comments *</label><textarea name="review_comments" rows="3" required></textarea>
                                    <div class="actions"><button class="button primary">Submit review</button></div>
                                </form>
                            </details>
                        @else
                            <small>{{ $result->review_comments ?: 'Awaiting independent review' }}</small>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        <div class="pagination">{{ $results->links() }}</div>
    @endif
</section>
@endsection
