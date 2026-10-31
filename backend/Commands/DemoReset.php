<?php

namespace Modules\Demo\Commands;

use App\Console\Commands\ResetCommand;
use App\Contracts\ScreenshotService;
use Cache;
use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Foundation\Console\UpCommand;
use Illuminate\Support\Carbon;
use Modules\Demo\Database\Seeders\DemoSeeder;
use Modules\Demo\Services\DemoScreenshotService;
use Modules\Demo\Services\WorkEmulator;

/**
 * Class DemoReset
 */
class DemoReset extends Command
{
    /** When the scheduler resets the demo */
    public const SCHEDULE = '0 */3 * * *';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:demo:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cattr demo flush';

    public function handle(ScreenshotService $screenshotService, WorkEmulator $emulator): int
    {
        $this->call(DownCommand::class);

        try {
            // Only the core's initial data: the demo accounts and projects come from the module seeder
            $this->call(ResetCommand::class, [
                '--force' => true,
                '--images' => true,
            ]);

            $this->call(SeedCommand::class, [
                '--class' => DemoSeeder::class,
                '--force' => true,
            ]);

            if ($screenshotService instanceof DemoScreenshotService) {
                $screenshotService->ensurePool();
            }

            $this->call(PlanWork::class);

            $created = $emulator->backfill(now()->startOfDay(), now(), Cache::get(WorkEmulator::PLAN_CACHE_KEY, []));
            $this->info("Emulated $created intervals since midnight");
        } finally {
            $this->call(UpCommand::class);
        }

        return 0;
    }

    public static function nextResetAt(): Carbon
    {
        $timezone = config('app.schedule_timezone') ?? config('app.timezone');

        return Carbon::instance((new CronExpression(self::SCHEDULE))->getNextRunDate(now($timezone), 0, false, $timezone));
    }
}
