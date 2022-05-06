<?php

namespace Modules\Demo\Providers;

use Filter;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Demo\Commands\DemoReset;
use Modules\Demo\Commands\EmulateWork;
use Modules\Demo\Commands\PlanWork;

class ModuleProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(DemoScreenshotServiceProvider::class);

        $this->commands([
            DemoReset::class,
            EmulateWork::class,
            PlanWork::class,
        ]);

        Filter::listen('filter.request.users.edit', static function($requestData) {
            unset($requestData['password']);

            return $requestData;
        });
    }

    public function boot(): void
    {
        $this->app->booted(static function () {
            $schedule = app(Schedule::class);

            $schedule->command(EmulateWork::class)
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground();

            $schedule->command(DemoReset::class)->daily();
        });
    }
}
