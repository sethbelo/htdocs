<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('erp:task-reminders')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('erp:daily-checks')->dailyAt('07:00');
Schedule::command('activitylog:clean')->monthly();
Schedule::command('erp:accounting-sync')->hourly()->withoutOverlapping();
