<?php
namespace App\\Http\\Controllers;
use App\\Models\\DiseaseCase;
use App\\Models\\Farm;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Str;
class DiseaseCaseController extends Controller {
 public function index() { return view('cases.index',['cases'=>DiseaseCase::with('farm')->latest()->paginate(15)]); }
 public function create() { return view('cases.create',['farms'=>Farm::orderBy('name')->get()]); }
 public function store(Request $request) {
  $data=$request->validate(['farm_id'=>'required|exists:farms,id','disease_suspected'=>'required|string|max:160','date_observed'=>'required|date|before_or_equal:today','symptoms'=>'required|string|max:5000','birds_affected'=>'required|integer|min:0','birds_dead'=>'required|integer|min:0|lte:birds_affected','severity'=>'required|in:low,medium,high,critical']);
  $data['case_code']='CASE-'.now()->format('ymd').'-'.Str::upper(Str::random(5)); $data['reported_by']=$request->user()->id; $data['status']='reported';
  DiseaseCase::create($data);
  return redirect()->route('cases.index')->with('status','Disease case reported successfully.');
 }
}
