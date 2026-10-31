<?php

namespace Modules\Demo\Providers;

use App\Exceptions\Entities\AuthorizationException;
use Filter;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Demo\Commands\DemoReset;
use Modules\Demo\Commands\EmulateWork;
use Modules\Demo\Commands\PlanWork;
use Modules\Demo\Support\DemoAccounts;

class ModuleServiceProvider extends ServiceProvider
{
    /** Fields of a demo account that nobody may change: they would break its sign-in or role */
    private const PROTECTED_ACCOUNT_FIELDS = ['email', 'password', 'role_id', 'active'];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'demo');

        $this->commands([
            DemoReset::class,
            EmulateWork::class,
            PlanWork::class,
        ]);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        $this->app->booted(static function () {
            $schedule = app(Schedule::class);

            $schedule->command(EmulateWork::class)
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground();

            $schedule->command(DemoReset::class)->cron(DemoReset::SCHEDULE);
        });
    }

    public static function registerEvents(): void
    {
        Filter::listen('filter.request.users.edit', static function ($requestData) {
            unset($requestData['password']);

            if (DemoAccounts::isDemoAccount($requestData['id'] ?? null)) {
                foreach (self::PROTECTED_ACCOUNT_FIELDS as $field) {
                    unset($requestData[$field]);
                }
            }

            return $requestData;
        });

        Filter::listen('filter.request.users.remove', static function ($id) {
            throw_if(
                DemoAccounts::isDemoAccount($id),
                new AuthorizationException(AuthorizationException::ERROR_TYPE_FORBIDDEN)
            );

            return $id;
        });
    }
}
