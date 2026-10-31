<?php

namespace Modules\Demo\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\Demo\Services\WorkSchedule;

/**
 * Class PlanWork
 */
class PlanWork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:demo:plan {date? : Day in the application timezone, today by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the demo work schedule and how many people work each hour (only for demo)';

    public function handle(WorkSchedule $schedule): int
    {
        $day = CarbonImmutable::parse($this->argument('date') ?? 'today')->startOfDay();
        $minOnline = (int)config('demo.min_online');

        $rows = [];
        foreach ($schedule->workers() as $worker) {
            $slots = array_keys($schedule->day($worker, $day->toDateString()));

            $rows[] = [
                $worker['user']->full_name,
                $worker['timezone'],
                $slots ? self::time(min($slots)) . '–' . self::time(max($slots) + 1) : '—',
                round(count($slots) * WorkSchedule::SLOT_MINUTES / 60, 1),
            ];
        }

        $this->table(['Worker', 'Timezone', 'Local hours', 'Hours of work'], $rows);

        $online = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $start = $day->addHours($hour)->addMinutes(30);
            $online[] = sprintf('%02d:30 %d', $hour, count($schedule->activeAt($start, $minOnline)));
        }

        $this->line('At work (application timezone): ' . implode(', ', $online));

        return 0;
    }

    private static function time(int $slot): string
    {
        $minutes = $slot * WorkSchedule::SLOT_MINUTES;

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
