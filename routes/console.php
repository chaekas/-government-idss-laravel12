<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\ScheduledReportGenerator;
use App\Services\IntegrationOutboxService;

Artisan::command('idss:about', function () {
    $this->info('Integrated Disease Surveillance System - Laravel 12');
})->purpose('Display IDSS application information');

Artisan::command('idss:run-scheduled-reports', function (ScheduledReportGenerator $generator) {
    $count = $generator->runDue();
    $this->info($count.' scheduled report(s) generated.');
})->purpose('Generate due IDSS scheduled reports');

Schedule::command('idss:run-scheduled-reports')->everyMinute()->withoutOverlapping();

Artisan::command('idss:process-integrations', function (IntegrationOutboxService $service) {
    $count = $service->processPending(50);
    $this->info($count.' integration event(s) delivered. Unconfigured providers remain blocked.');
})->purpose('Process approved outbound integration events');

Schedule::command('idss:process-integrations')->everyMinute()->withoutOverlapping();
