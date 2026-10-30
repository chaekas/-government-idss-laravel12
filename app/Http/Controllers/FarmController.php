<?php
namespace App\Http\Controllers;
use App\Models\Farm;
use App\Models\Farmer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class FarmController extends Controller {
 public function index() { return view('farms.index',['farms'=>Farm::with('farmer')->latest()->paginate(15)]); }
 public function create() { return view('farms.create',['farmers'=>Farmer::orderBy('full_name')->get()]); }
 public function store(Request $request) {
  $data=$request->validate(['farmer_id'=>'required|exists:farmers,id','name'=>'required|string|max:160','region'=>'required|string|max:100','district'=>'required|string|max:100','ward'=>'nullable|string|max:100','village'=>'nullable|string|max:100','latitude'=>'nullable|numeric|between:-90,90','longitude'=>'nullable|numeric|between:-180,180','poultry_type'=>'nullable|string|max:100','bird_count'=>'required|integer|min:0','notes'=>'nullable|string|max:3000']);
  $data['farm_code']='FARM-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
  Farm::create($data);
  return redirect()->route('farms.index')->with('status','Farm registered successfully.');
 }
}
