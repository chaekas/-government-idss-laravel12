<?php

namespace App\Http\Controllers;

use App\Models\IntegrationLog;
use App\Models\IntegrationOutbox;
use App\Services\IntegrationOutboxService;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless(in_array($request->user()->role, ['system_admin','super_admin','super-admin','administrator','admin'], true), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);
        $providers = config('idss_integrations.providers', []);

        return view('integrations.index', [
            'providers' => $providers,
            'outbox' => IntegrationOutbox::latest()->paginate(15, ['*'], 'outbox_page'),
            'logs' => IntegrationLog::latest('occurred_at')->paginate(15, ['*'], 'logs_page'),
        ]);
    }

    public function process(Request $request, IntegrationOutboxService $service)
    {
        $this->authorizeAdmin($request);
        $processed = $service->processPending(50);
        return redirect()->route('integrations.index')->with('status', $processed.' outbound integration item(s) delivered. Disabled or unconfigured providers remain blocked.');
    }
}
