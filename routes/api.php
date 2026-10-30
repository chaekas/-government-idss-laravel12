<?php
use Illuminate\\Support\\Facades\\Route;
Route::get('/health',fn()=>response()->json(['status'=>'ok','service'=>'government-idss-api','version'=>'0.1.0']));
