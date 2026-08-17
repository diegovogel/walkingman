<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Every minute, not hourly: the command departs the new trip at the moment it
// runs, so its cadence is exactly how long the walking man stands still between
// trips. It costs one exists() query to find a trip already underway and stop.
Schedule::command('trip:next')->everyMinute()->withoutOverlapping();
