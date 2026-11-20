<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('firebooster:status',function(){$this->info('Firebooster application booted.');})->purpose('Check Firebooster console availability');
