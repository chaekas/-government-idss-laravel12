<?php
use Illuminate\\Support\\Facades\\Artisan;
Artisan::command('idss:about', function () { $this->info('Integrated Disease Surveillance System - Laravel 12'); })->purpose('Display IDSS application information');
