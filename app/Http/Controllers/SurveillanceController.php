<?php

namespace App\Http\Controllers;

use App\Models\DiseaseCase;
use App\Models\Investigation;
use App\Models\LaboratoryTestResult;
use App\Models\Outbreak;
use App\Models\Sample;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class SurveillanceController extends Controller
{
    public function index(Request $request)
    {
        $filters=$request->validate(['from'=>['nullable','date'],'to'=>['nullable','date','after_or_equal:from']]);
        $base=DiseaseCase::with(['farm','diseaseCategory']);
        if(!empty($filters['from'])) $base->whereDate('date_observed','>=',$filters['from']);
        if(!empty($filters['to'])) $base->whereDate('date_observed','<=',$filters['to']);
        $cases=$base->get();

        $statusCounts=$cases->groupBy('status')->map->count()->sortDesc();
        $diseaseCounts=$cases->groupBy(fn($case)=>$case->diseaseCategory?->name ?: $case->disease_suspected)->map->count()->sortDesc()->take(10);
        $speciesCounts=$cases->groupBy(fn($case)=>$case->affected_species ?: 'Not recorded')->map->count()->sortDesc();
        $hotspots=$cases->groupBy(fn($case)=>trim(($case->farm?->district ?: 'Unknown district').', '.($case->farm?->region ?: 'Unknown region')))->map(function($items){return ['cases'=>$items->count(),'deaths'=>$items->sum('birds_dead'),'affected'=>$items->sum('birds_affected')];})->sortByDesc('cases')->take(10);
        $monthly=$cases->groupBy(fn($case)=>($case->date_observed ?: $case->created_at)->format('Y-m'))->map->count()->sortKeys();
        $samples=Sample::whereNotNull('released_at')->get(['created_at','released_at']);
        $turnaroundHours=$samples->map(fn($sample)=>$sample->created_at?->diffInHours($sample->released_at))->filter(fn($hours)=>$hours!==null);
        $from=$filters['from']??now()->subMonths(11)->startOfMonth()->toDateString();
        $to=$filters['to']??now()->toDateString();
        $monthlyTrend=[];
        $cursor=\Illuminate\Support\Carbon::parse($from)->startOfMonth();
        $end=\Illuminate\Support\Carbon::parse($to)->startOfMonth();
        while($cursor<=$end){$key=$cursor->format('Y-m');$monthlyTrend[]=['month'=>$key,'cases'=>$monthly->get($key,0)];$cursor=$cursor->copy()->addMonth();}

        return view('surveillance.index',[
            'from'=>$filters['from']??'','to'=>$filters['to']??'',
            'caseCount'=>$cases->count(),'openCases'=>$cases->whereIn('status',['reported','under_investigation','sample_collected','laboratory_testing'])->count(),
            'confirmedCases'=>$cases->where('status','confirmed')->count(),'rejectedCases'=>$cases->where('status','rejected')->count(),
            'investigationCount'=>Investigation::whereIn('status',['under_investigation','sample_collected','completed'])->count(),
            'pendingInvestigations'=>Investigation::whereIn('status',['under_investigation','sample_collected'])->count(),
            'activeOutbreaks'=>Outbreak::whereIn('status',['suspected','active'])->count(),
            'statusCounts'=>$statusCounts,'diseaseCounts'=>$diseaseCounts,'speciesCounts'=>$speciesCounts,'hotspots'=>$hotspots,'monthlyTrend'=>$monthlyTrend,
            'averageTurnaroundHours'=>$turnaroundHours->isNotEmpty()?round($turnaroundHours->avg(),1):null,
            'releasedSamples'=>$turnaroundHours->count(),
            'unreadAlerts'=>UserNotification::where('user_id',$request->user()->id)->whereNull('read_at')->whereIn('category',['surveillance','outbreak'])->count(),
            'recentAlerts'=>UserNotification::where('user_id',$request->user()->id)->whereIn('category',['surveillance','outbreak'])->latest()->take(8)->get(),
            'outbreaksByRegion'=>Outbreak::whereIn('status',['suspected','active'])->selectRaw('region, district, COUNT(*) as total')->groupBy('region','district')->orderByDesc('total')->limit(10)->get(),
        ]);
    }
}
