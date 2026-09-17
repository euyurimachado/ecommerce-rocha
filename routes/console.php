<?php

use App\Support\CoreUpdates\ReleaseChecker;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(ReleaseChecker::class)->check())
    ->daily()
    ->name('ecommerce-core:check-releases')
    ->withoutOverlapping();
