<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Laboratory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LaboratoryController extends Controller
{
    public function index()
    {
        return view('laboratories.index', ['laboratories' => Laboratory::orderBy('name')->paginate(20)]);
    }

    public function create()
    {
        return view('laboratories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'level' => ['required', 'in:district,regional,zonal,national,private'],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:180'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
        ]);
        $data['code'] = 'LAB-'.Str::upper(Str::random(8));
        $data['is_active'] = true;
        $laboratory = Laboratory::create($data);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'laboratory.created', 'entity_type' => 'laboratory', 'entity_id' => (string) $laboratory->id, 'ip_address' => $request->ip(), 'description' => 'Registered laboratory '.$laboratory->code, 'created_at' => now()]);
        return redirect()->route('laboratories.index')->with('status', 'Laboratory registered successfully.');
    }
}
