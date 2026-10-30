<?php
namespace App\\Http\\Controllers;
use App\\Models\\Farmer;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Str;
class FarmerController extends Controller {
 public function index() { return view('farmers.index',['farmers'=>Farmer::latest()->paginate(15)]); }
 public function create() { return view('farmers.create'); }
 public function store(Request $request) {
  $data=$request->validate(['full_name'=>'required|string|max:160','phone'=>'nullable|string|max:40','email'=>'nullable|email|max:160','national_id'=>'nullable|string|max:100','region'=>'required|string|max:100','district'=>'required|string|max:100','ward'=>'nullable|string|max:100','village'=>'nullable|string|max:100','address'=>'nullable|string|max:255']);
  $data['farmer_code']='FRM-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
  Farmer::create($data);
  return redirect()->route('farmers.index')->with('status','Farmer registered successfully.');
 }
}
