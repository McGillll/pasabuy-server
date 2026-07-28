<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:send-overdue-digest')->daily();

Schedule::call(function () {
    DB::table('cache')->where('expiration', '<=', now()->timestamp)->delete();
})->daily()->name('prune-expired-cache');
