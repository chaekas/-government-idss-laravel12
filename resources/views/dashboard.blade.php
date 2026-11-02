@extends('layouts.app')
@section('title','Dashboard')
@section('content')
@php
    $mode = $dashboardMode ?? 'surveillance';
    $role = auth()->user()->role;
    $farmerNeedsFarm = $mode === 'farmer' && (int) ($farmCount ?? 0) === 0;
@endphp
<div class="heading">
    <div>
        <small class="kicker">OVERVIEW / {{ $mode === 'laboratory' ? 'LABORATORY WORKFLOW' : ($mode === 'farmer' ? 'FARM HEALTH' : 'SURVEILLANCE') }}</small>
        <h1>{{ $mode === 'laboratory' ? 'Laboratory dashboard' : ($mode === 'farmer' ? 'Farm health dashboard' : ($role === 'system_admin' ? 'System administration dashboard' : ($role === 'mlf_officer' ? 'National surveillance dashboard' : ($role === 'regional_veterinary_officer' ? 'Regional surveillance dashboard' : 'Surveillance dashboard')))) }}</h1>
        <p>{{ $mode === 'laboratory' ? 'Monitor sample reception and laboratory result reviews.' : ($mode === 'farmer' ? 'View information for your registered farms and report suspected poultry disease events.' : ($role === 'system_admin' ? 'Manage authorized access and monitor the integrated surveillance workflow.' : ($role === 'mlf_officer' ? 'Review national surveillance indicators, disease trends, and outbreak response.' : ($role === 'regional_veterinary_officer' ? 'Review surveillance reporting and coordinate regional follow-up.' : 'Monitor field reporting, disease signals, and laboratory workload.')))) }}</p>
    </div>
    @if(in_array($role, ['field_officer','regional_veterinary_officer','mlf_officer','system_admin'], true) || ($role === 'farmer' && !$farmerNeedsFarm))
        <a class="button primary" href="{{ route('cases.create') }}">＋ Report disease case</a>
    @endif
</div>

<div class="notice"><b>i</b><div><strong>Operational overview</strong><p>Counts reflect records currently stored in this system. Suspected cases and unapproved laboratory findings are not official diagnoses; follow approved Ministry procedures.</p></div></div>

@if($farmerNeedsFarm)
    <section class="panel">
        <div class="empty">
            <b>Your farmer account is ready</b>
            <p>Your farm has not yet been registered in the surveillance system. Contact your Poultry Field Officer so they can register and link your farm. Once it is linked to your account, you can submit disease reports and follow their status here.</p>
        </div>
    </section>
@endif

@if($mode === 'laboratory')
    <div class="stats">
        <article><span>Samples tracked</span><strong>{{ number_format($sampleCount) }}</strong><small>Samples in the register</small></article>
        <article><span>Pending sample workflow</span><strong>{{ number_format($pendingSampleCount) }}</strong><small>Awaiting completion</small></article>
        <article><span>Results awaiting review</span><strong>{{ number_format($pendingResultCount) }}</strong><small>Require an independent reviewer</small></article>
    </div>
    <section class="panel">
        <div class="panel-head"><div><h2>Laboratory actions</h2><p>Continue the sample-to-result workflow permitted for your role.</p></div></div>
        <div class="actions" style="padding:18px">
            <a class="button primary" href="{{ route('samples.index') }}">Open sample custody</a>
            @if(in_array($role, ['laboratory_technician','system_admin'], true))
                <a class="button secondary" href="{{ route('laboratory.create') }}">Record test result</a>
            @endif
            @if(in_array($role, ['laboratory_manager','system_admin'], true))
                <a class="button secondary" href="{{ route('laboratory.index') }}">Review laboratory results</a>
            @endif
        </div>
    </section>
@else
    <div class="stats">
        <article><span>{{ $mode === 'farmer' ? 'Your farmer profile' : 'Registered farmers' }}</span><strong>{{ number_format($farmerCount) }}</strong><small>Farmer records</small></article>
        <article><span>{{ $mode === 'farmer' ? 'Your farms' : 'Registered farms' }}</span><strong>{{ number_format($farmCount) }}</strong><small>Poultry locations</small></article>
        <article><span>{{ $mode === 'farmer' ? 'Cases for your farms' : 'Reported cases' }}</span><strong>{{ number_format($caseCount) }}</strong><small>Recorded reports</small></article>
        <article><span>Cases requiring attention</span><strong>{{ number_format($openCaseCount) }}</strong><small>Reported, investigating, sampled or in laboratory</small></article>
        @if($mode !== 'farmer')
            <article><span>Registered animals</span><strong>{{ number_format($animalCount) }}</strong><small>Animal registry</small></article>
            <article><span>Active investigations</span><strong>{{ number_format($investigationCount) }}</strong><small>Field follow-up</small></article>
            <article><span>Samples tracked</span><strong>{{ number_format($sampleCount) }}</strong><small>{{ number_format($pendingSampleCount) }} pending workflow</small></article>
            <article><span>Suspected / active outbreaks</span><strong>{{ number_format($outbreakCount) }}</strong><small>Require surveillance review</small></article>
        @endif
    </div>
    @if(!$farmerNeedsFarm)
        <section class="panel">
            <div class="panel-head"><div><h2>{{ $mode === 'farmer' ? 'Cases for your farms' : 'Recent disease reports' }}</h2><p>Latest cases in the register available to your account</p></div><a href="{{ route('cases.index') }}">View all reports →</a></div>
            @if($recentCases->isEmpty())
                <div class="empty"><b>No disease reports yet</b><p>{{ $mode === 'farmer' ? 'Reports for your registered farms will appear here.' : 'Once a disease case is reported for your surveillance area, it will appear here.' }}</p>
                    @if($mode !== 'farmer' || !$farmerNeedsFarm)<a class="button secondary" href="{{ route('cases.create') }}">Create first report</a>@endif
                </div>
            @else
                <div class="table-wrap"><table><thead><tr><th>Case ID</th><th>Suspected disease</th><th>Farm</th><th>Date observed</th><th>Severity</th><th>Status</th></tr></thead><tbody>
                @foreach($recentCases as $case)
                    <tr><td>{{ $case->case_code }}</td><td><b>{{ $case->disease_suspected }}</b></td><td>{{ $case->farm?->name ?? '—' }}</td><td>{{ $case->date_observed?->format('d M Y') }}</td><td><span class="badge {{ $case->severity }}">{{ ucfirst($case->severity) }}</span></td><td>{{ str_replace('_',' ',ucfirst($case->status)) }}</td></tr>
                @endforeach
                </tbody></table></div>
            @endif
        </section>
    @endif
@endif
@endsection
