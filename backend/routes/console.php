<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('firebooster:status', function () {
    $this->info('Firebooster application booted.');
})->purpose('Check Firebooster console availability');

Schedule::command('smm-center:sync-order-statuses')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('smm-center:check-balance')
    ->hourly()
    ->withoutOverlapping();
