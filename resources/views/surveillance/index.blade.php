@extends('layouts.app')
@section('title','Surveillance analytics')
@section('content')
<div class="heading"><div><small class="kicker">SURVEILLANCE / EPIDEMIOLOGICAL MONITORING</small><h1>Disease surveillance analytics</h1><p>Operational monitoring of reported cases, disease status, trends, species, geographic hotspots and laboratory turnaround.</p></div><a class="button secondary" href="{{ route('cases.index') }}">Search case register →</a></div>
<form method="GET" class="panel toolbar"><label>Observed from <input type="date" name="from" value="{{ $from }}"></label><label>Observed to <input type="date" name="to" value="{{ $to }}"></label><button class="button primary">Apply period</button><a class="button secondary" href="{{ route('surveillance.index') }}">Last 12 months</a></form>
<div class="stats">
<article><span>Reported cases</span><strong>{{ number_format($caseCount) }}</strong><small>Within selected observation period</small></article>
<article><span>Cases requiring attention</span><strong>{{ number_format($openCases) }}</strong><small>New, investigating, sampled or in lab</small></article>
<article><span>Confirmed cases</span><strong>{{ number_format($confirmedCases) }}</strong><small>Following approved lab results</small></article>
<article><span>Rejected cases</span><strong>{{ number_format($rejectedCases) }}</strong><small>Negative approved diagnostic results</small></article>
<article><span>Active investigations</span><strong>{{ number_format($pendingInvestigations) }}</strong><small>{{ number_format($investigationCount) }} investigations in workflow</small></article>
<article><span>Suspected / active outbreaks</span><strong>{{ number_format($activeOutbreaks) }}</strong><small>Require surveillance follow-up</small></article>
<article><span>Average laboratory turnaround</span><strong>{{ $averageTurnaroundHours !== null ? $averageTurnaroundHours.' hrs' : '—' }}</strong><small>Across {{ $releasedSamples }} released samples</small></article>
</div>
<div class="panel"><div class="panel-head"><div><h2>Reported case trend</h2><p>Cases grouped by month of observation</p></div></div>
@if(collect($monthlyTrend)->sum('cases')===0)<div class="empty">No cases recorded for this period.</div>@else
<div class="table-wrap"><table><thead><tr><th>Month</th><th>Reported cases</th><th>Visual trend</th></tr></thead><tbody>@foreach($monthlyTrend as $month)<tr><td>{{ $month['month'] }}</td><td>{{ $month['cases'] }}</td><td><div style="background:#dbe7df;border-radius:5px;height:10px;width:100%"><div style="background:#28734b;border-radius:5px;height:10px;width:{{ $month['cases'] ? max(2,($month['cases']/max(1,collect($monthlyTrend)->max('cases')))*100) : 0 }}%"></div></div></td></tr>@endforeach</tbody></table></div>@endif
</div>
<div class="grid two">
<section class="panel"><div class="panel-head"><div><h2>Case status distribution</h2><p>Current workflow status</p></div></div>
@forelse($statusCounts as $status=>$count)<p><b>{{ ['reported'=>'New','under_investigation'=>'Under investigation','sample_collected'=>'Sample collected','laboratory_testing'=>'Laboratory testing','confirmed'=>'Confirmed','rejected'=>'Rejected','closed'=>'Closed'][$status] ?? str_replace('_',' ',ucfirst($status)) }}</b><span style="float:right">{{ $count }}</span></p>@empty<p>No case records.</p>@endforelse
</section>
<section class="panel"><div class="panel-head"><div><h2>Disease classification / suspected disease</h2><p>Top reported categories</p></div></div>
@forelse($diseaseCounts as $name=>$count)<p><b>{{ $name }}</b><span style="float:right">{{ $count }}</span></p>@empty<p>No disease records.</p>@endforelse
</section>
<section class="panel"><div class="panel-head"><div><h2>Species distribution</h2><p>Reported affected species</p></div></div>
@forelse($speciesCounts as $name=>$count)<p><b>{{ $name }}</b><span style="float:right">{{ $count }}</span></p>@empty<p>No species data recorded.</p>@endforelse
</section>
<section class="panel"><div class="panel-head"><div><h2>Geographic hotspots</h2><p>Districts ranked by reported cases</p></div></div>
@forelse($hotspots as $location=>$metrics)<p><b>{{ $location }}</b><span style="float:right">{{ $metrics['cases'] }} cases</span><small>{{ number_format($metrics['affected']) }} affected · {{ number_format($metrics['deaths']) }} deaths</small></p>@empty<p>No geographic case data.</p>@endforelse
</section>
</div>
<section class="panel"><div class="panel-head"><div><h2>Outbreak distribution</h2><p>Suspected and active outbreaks by district</p></div></div>
@if($outbreaksByRegion->isEmpty())<p>No active outbreak records.</p>@else<div class="table-wrap"><table><thead><tr><th>Region</th><th>District</th><th>Outbreaks</th></tr></thead><tbody>@foreach($outbreaksByRegion as $item)<tr><td>{{ $item->region }}</td><td>{{ $item->district }}</td><td>{{ $item->total }}</td></tr>@endforeach</tbody></table></div>@endif
</section>
<div class="notice"><b>!</b><div><strong>Incidence and prevalence denominator not yet available</strong><p>The SRS requires incidence and prevalence statistics (FR-146). The current farm registry stores a general bird-count field but does not hold validated, dated poultry population denominators by species and administrative area. This dashboard therefore reports observed case counts and does not present an unsupported incidence or prevalence rate. Ministry-approved population baseline data is required to complete FR-146 accurately.</p></div></div>
@endsection
