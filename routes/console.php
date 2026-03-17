<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule Horizon snapshot every 5 minutes
Schedule::command('horizon:snapshot')->everyFiveMinutes()->withoutOverlapping();

// Schedule custom snapshot command every 10 minutes (for logging)
Schedule::command('app:horizon-snapshot-command')->everyTenMinutes()->withoutOverlapping();
