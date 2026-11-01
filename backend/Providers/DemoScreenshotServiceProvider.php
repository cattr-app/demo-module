<?php

namespace Modules\Demo\Providers;

use App\Contracts\ScreenshotService;
use Illuminate\Support\ServiceProvider;
use Modules\Demo\Services\DemoScreenshotService;

class DemoScreenshotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The core binds ScreenshotService in a deferred provider that is loaded after module providers
        // and would replace a plain binding, so the module decorates the resolved service instead.
        $this->app->extend(
            ScreenshotService::class,
            fn () => $this->app->make(DemoScreenshotService::class)
        );
    }
}
