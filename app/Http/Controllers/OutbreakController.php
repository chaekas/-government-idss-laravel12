<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Outbreak;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OutbreakController extends Controller
{
    public function index()
    {
        return view('outbreaks.index', ['outbreaks' => Outbreak::latest()->paginate(15)]);
    }

    public function create()
    {
        return view('outbreaks.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'disease_name' => ['required', 'string', 'max:160'],
            'region' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'cases_count' => ['required', 'integer', 'min:1'],
            'deaths_count' => ['required', 'integer', 'min:0', 'lte:cases_count'],
            'severity' => ['required', 'in:low,medium,high,critical'],
            'response_actions' => ['nullable', 'string', 'max:5000'],
        ]);
        $data['outbreak_code'] = 'OUT-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        $data['reported_by'] = $request->user()->id;
        $data['status'] = 'suspected';
        $outbreak = Outbreak::create($data);
        User::where('is_active', true)->whereIn('role', ['field_officer', 'regional_veterinary_officer', 'mlf_officer', 'system_admin'])->get()->each(function (User $recipient) use ($outbreak) {
            UserNotification::create(['user_id' => $recipient->id, 'title' => 'Suspected disease outbreak recorded', 'message' => $outbreak->outbreak_code.' — '.$outbreak->disease_name.' in '.$outbreak->district.', '.$outbreak->region.'. Verify through the official investigation workflow.', 'category' => 'outbreak']);
        });
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'outbreak.created', 'entity_type' => 'outbreak', 'entity_id' => (string) $outbreak->id, 'ip_address' => $request->ip(), 'description' => 'Recorded outbreak '.$outbreak->outbreak_code, 'created_at' => now()]);
        return redirect()->route('outbreaks.index')->with('status', 'Outbreak record created. Confirm details through the investigation workflow.');
    }
}
