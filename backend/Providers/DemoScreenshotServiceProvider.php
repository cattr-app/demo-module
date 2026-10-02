<?php

namespace Modules\Demo\Providers;

use App\Contracts\ScreenshotService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Modules\Demo\Services\DemoScreenshotService;

class DemoScreenshotServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function provides(): array
    {
        return [ScreenshotService::class];
    }

    public array $bindings = [
        ScreenshotService::class => DemoScreenshotService::class,
    ];
}
