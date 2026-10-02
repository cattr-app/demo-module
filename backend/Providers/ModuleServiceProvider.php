<?php

namespace Modules\Demo\Providers;

use Filter;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Demo\Commands\DemoReset;
use Modules\Demo\Commands\EmulateWork;
use Modules\Demo\Commands\PlanWork;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            DemoReset::class,
            EmulateWork::class,
            PlanWork::class,
        ]);
    }

    public function boot(): void
    {
        $this->app->booted(static function () {
            $schedule = app(Schedule::class);

            $schedule->command(EmulateWork::class)
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground();

            $schedule->command(DemoReset::class)->cron('0 */3 * * *');
        });
    }

    public static function registerEvents(): void
    {
        Filter::listen('filter.request.users.edit', static function ($requestData) {
            unset($requestData['password']);

            return $requestData;
        });
    }
}
