<?php

namespace Modules\Demo\Commands;

use App\Console\Commands\RecreateCronTaskWorkers;
use App\Console\Commands\ResetCommand;
use App\Contracts\ScreenshotService;
use Cache;
use Cron\CronExpression;
use Illuminate\Contracts\Cache\LockTimeoutException;
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

    /** Lock shared with the emulator, so resets and emulator runs never write at the same time */
    public const LOCK = 'demo:reset';
    private const LOCK_SECONDS = 900;
    private const LOCK_WAIT_SECONDS = 60;
    private const PAGE_REFRESH_SECONDS = 5;

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
        $lock = Cache::lock(self::LOCK, self::LOCK_SECONDS);

        try {
            // An emulator run holds the lock for a moment; another reset holds it until it finishes
            $lock->block(self::LOCK_WAIT_SECONDS);
        } catch (LockTimeoutException) {
            $this->warn('Another demo reset is running');

            return 1;
        }

        // A reset started in a separate maintenance window leaves the application down
        $wasDown = $this->laravel->isDownForMaintenance();

        if (!$wasDown) {
            // Visitors get a "being reset" page that reloads itself until the demo is back
            $this->call(DownCommand::class, [
                '--render' => 'demo::reset',
                '--refresh' => self::PAGE_REFRESH_SECONDS,
                '--retry' => self::PAGE_REFRESH_SECONDS,
            ]);
        }

        try {
            // Only the core's initial data: the demo company comes from the module seeder
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

            $days = config('demo.history_days');
            $created = $emulator->backfill(now()->subDays($days)->startOfDay(), now());
            $this->info("Emulated $created intervals for $days days and today");

            DemoSeeder::calibrateEstimates();

            // Totals of tracked time per task and user, which the bulk insert does not update
            $this->callSilently(RecreateCronTaskWorkers::class);
        } finally {
            if (!$wasDown) {
                $this->call(UpCommand::class);
            }

            $lock->release();
        }

        return 0;
    }

    public static function nextResetAt(): Carbon
    {
        $timezone = config('app.schedule_timezone') ?? config('app.timezone');

        return Carbon::instance((new CronExpression(self::SCHEDULE))->getNextRunDate(now($timezone), 0, false, $timezone));
    }
}
