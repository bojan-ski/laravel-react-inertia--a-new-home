<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('listings:purge-rejected')->daily();

Schedule::command('listings:send-expiry-reminders')->dailyAt('08:00')->withoutOverlapping(60);

Schedule::command('saved-searches:send-digest')->dailyAt('07:00')->timezone('Europe/Belgrade')->withoutOverlapping(60);
