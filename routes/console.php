<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('prayer:generate-schedule')->dailyAt('00:01');
Schedule::command('assets:maintenance-reminders')->dailyAt('07:00');
Schedule::command('tpq:generate-spp-bills')->monthlyOn(1, '06:00');
Schedule::command('tpq:spp-reminders')->dailyAt('08:00');
Schedule::command('imam:send-reminders')->dailyAt('20:00');
