<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeArea;
use App\Models\DiseaseCase;
use App\Models\DiseaseCategory;
use App\Models\Farm;
use App\Models\Outbreak;
use App\Services\UserDataScope;
use Illuminate\Http\Request;

class GISController extends Controller
{
    public function index(Request $request)
    {
        $filters=$request->validate([
            'disease_category_id'=>['nullable','exists:disease_categories,id'],
            'species'=>['nullable','string','max:120'],
            'from'=>['nullable','date'],'to'=>['nullable','date','after_or_equal:from'],
            'administrative_area_id'=>['nullable','exists:administrative_areas,id'],
        ]);
        $points=[];
        $farms=app(UserDataScope::class)->farms(Farm::whereNotNull('latitude')->whereNotNull('longitude')->with('farmer'), $request->user())
            ->when(!empty($filters['administrative_area_id']),fn($q)=>$q->where('administrative_area_id',$filters['administrative_area_id']))->get();
        foreach($farms as $farm) $points[]=['type'=>'farm','label'=>$farm->name,'code'=>$farm->farm_code,'latitude'=>(float)$farm->latitude,'longitude'=>(float)$farm->longitude,'detail'=>$farm->district.', '.$farm->region.' · Farmer: '.($farm->farmer?->full_name ?? 'Not recorded')];

        $cases=app(UserDataScope::class)->cases(DiseaseCase::with(['farm','diseaseCategory']), $request->user())
            ->where(function($q){$q->where(fn($n)=>$n->whereNotNull('latitude')->whereNotNull('longitude'))->orWhereHas('farm',fn($farm)=>$farm->whereNotNull('latitude')->whereNotNull('longitude'));})
            ->when(!empty($filters['disease_category_id']),fn($q)=>$q->where('disease_category_id',$filters['disease_category_id']))
            ->when(!empty($filters['species']),fn($q)=>$q->where('affected_species',$filters['species']))
            ->when(!empty($filters['from']),fn($q)=>$q->whereDate('date_observed','>=',$filters['from']))
            ->when(!empty($filters['to']),fn($q)=>$q->whereDate('date_observed','<=',$filters['to']))
            ->when(!empty($filters['administrative_area_id']),fn($q)=>$q->where(fn($n)=>$n->where('administrative_area_id',$filters['administrative_area_id'])->orWhereHas('farm',fn($farm)=>$farm->where('administrative_area_id',$filters['administrative_area_id']))))
            ->latest()->get();
        foreach($cases as $case) {
            $lat=$case->latitude ?? $case->farm?->latitude; $long=$case->longitude ?? $case->farm?->longitude;
            if($lat===null || $long===null) continue;
            $points[]=['type'=>'case','label'=>$case->diseaseCategory?->name ?: $case->disease_suspected,'code'=>$case->case_code,'latitude'=>(float)$lat,'longitude'=>(float)$long,'detail'=>'Farm '.($case->farm?->name ?? 'Not linked').' · '.$case->severity.' severity · '.str_replace('_',' ',$case->status)];
        }

        $outbreaks=app(UserDataScope::class)->outbreaks(Outbreak::whereNotNull('latitude')->whereNotNull('longitude'), $request->user())
            ->when(!empty($filters['disease_category_id']),fn($q)=>$q->where('disease_category_id',$filters['disease_category_id']))
            ->when(!empty($filters['from']),fn($q)=>$q->whereDate('start_date','>=',$filters['from']))
            ->when(!empty($filters['to']),fn($q)=>$q->whereDate('start_date','<=',$filters['to']))
            ->when(!empty($filters['administrative_area_id']),fn($q)=>$q->where('administrative_area_id',$filters['administrative_area_id']))
            ->latest()->get();
        foreach($outbreaks as $outbreak) $points[]=['type'=>'outbreak','label'=>$outbreak->disease_name,'code'=>$outbreak->outbreak_code,'latitude'=>(float)$outbreak->latitude,'longitude'=>(float)$outbreak->longitude,'detail'=>$outbreak->district.', '.$outbreak->region.' · '.str_replace('_',' ',$outbreak->status)];

        return view('gis.index',[
            'mapPoints'=>$points,'filters'=>$filters,
            'categories'=>DiseaseCategory::where('is_active',true)->orderBy('name')->get(),
            'areas'=>in_array($request->user()->role, ['system_admin','super_admin','super-admin','administrator','admin','mlf_officer'], true) ? AdministrativeArea::where('is_active',true)->orderBy('name')->get() : AdministrativeArea::whereIn('id',$request->user()->accessibleAdministrativeAreaIds() ?: [-1])->orderBy('name')->get(),
            'speciesOptions'=>app(UserDataScope::class)->cases(DiseaseCase::whereNotNull('affected_species'), $request->user())->distinct()->orderBy('affected_species')->pluck('affected_species'),
        ]);
    }
}
