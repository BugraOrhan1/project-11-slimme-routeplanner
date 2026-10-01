<?php

use App\Services\DeadlineAlerts;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('deadlines:check', function (DeadlineAlerts $alerts) {
    $this->info($alerts->generate().' nieuwe melding(en) aangemaakt.');
})->purpose('Maak meldingen voor deadlines die bijna verlopen (planner + bedrijfsleider)');

Schedule::command('deadlines:check')->dailyAt('07:00');
