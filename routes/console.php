<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//Artisan::command('inspire', function () {
//    $this->comment(Inspiring::quote());
//})->purpose('Display an inspiring quote')->hourly();



Schedule::command('app:update-currencies')->twiceMonthly(1, 14, '00:00');

Schedule::command('app:get-supported-wallets-for-coin')->twiceMonthly(1, 14, '03:00');

