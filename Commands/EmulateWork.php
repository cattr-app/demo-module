<?php

namespace Modules\Demo\Commands;

use App\Contracts\ScreenshotService;
use App\Models\TimeInterval;
use Illuminate\Console\Command;
use Storage;
use Cache;

/**
 * Class EmulateWork
 */
class EmulateWork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:demo:emulate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cattr Emulation of the work (only for demo)';

    private const INTERVAL_DURATION = 300;

    public function handle(): int
    {
        $plan = Cache::get('usersPlan');

        if (!$plan) {
            $this->call(PlanWork::class);
            return 1;
        }

        $now = now();

        $timeFromBeginOfTheDay = ((int)date('H') * 60) + ((int)date('i'));

        foreach ($plan as $entry) {
            foreach ($entry['intervals'] as $interval) {
                if ($timeFromBeginOfTheDay >= $interval['start'] && $timeFromBeginOfTheDay <= $interval['end']) {
                    TimeInterval::factory([
                        'task_id' => $interval['task'],
                        'user_id' => $entry['user'],
                        'start_at' => $now->subSeconds(self::INTERVAL_DURATION)->toIso8601String(),
                        'end_at' => $now->toIso8601String(),
                    ])->withScreenshot()->create();
                }
            }
        }

        return 0;
    }
}
