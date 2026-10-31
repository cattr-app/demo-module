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
    protected $description = 'Show the demo work of a day and the fewest people at work each hour (only for demo)';

    public function handle(WorkSchedule $schedule): int
    {
        $day = CarbonImmutable::parse($this->argument('date') ?? 'today')->startOfDay();
        $minOnline = (int)config('demo.min_online');

        // The same slots the emulator turns into intervals, with and without the extra hours
        $planned = [];
        $extra = [];
        $atWork = array_fill(0, 24, PHP_INT_MAX);

        for ($start = $day; $start < $day->addDay(); $start = $start->addMinutes(WorkSchedule::SLOT_MINUTES)) {
            $base = $schedule->activeAt($start);
            $active = $schedule->activeAt($start, $minOnline);
            $atWork[$start->hour] = min($atWork[$start->hour], count($active));

            foreach (array_keys($active) as $userId) {
                if (isset($base[$userId])) {
                    $planned[$userId][] = $start;
                } else {
                    $extra[$userId][] = $start;
                }
            }
        }

        $rows = [];
        foreach ($schedule->workers() as $userId => $worker) {
            $slots = array_merge($planned[$userId] ?? [], $extra[$userId] ?? []);
            usort($slots, static fn (CarbonImmutable $a, CarbonImmutable $b) => $a <=> $b);

            $rows[] = [
                $worker['user']->full_name,
                $worker['timezone'],
                self::periods($slots, $worker['timezone']),
                self::hours(count($planned[$userId] ?? [])),
                self::hours(count($extra[$userId] ?? [])),
            ];
        }

        $this->table(['Worker', 'Timezone', 'Local time at work', 'Planned hours', 'Extra hours'], $rows);

        $this->line('Fewest people at work per hour (application timezone): ' . implode(', ', array_map(
            static fn (int $hour, int $count) => sprintf('%02d:00 %d', $hour, $count),
            array_keys($atWork),
            $atWork
        )));

        return 0;
    }

    /**
     * Periods of work in the worker's local time, in the order of the day; gaps up to 90 minutes
     * (lunch, breaks) are part of a period.
     *
     * @param CarbonImmutable[] $slots slot starts in ascending order
     */
    private static function periods(array $slots, string $timezone): string
    {
        $periods = [];

        foreach ($slots as $start) {
            $end = $start->addMinutes(WorkSchedule::SLOT_MINUTES);
            $last = array_key_last($periods);

            if ($last !== null && $periods[$last][1]->diffInMinutes($start) <= 90) {
                $periods[$last][1] = $end;
            } else {
                $periods[] = [$start, $end];
            }
        }

        return $periods ? implode(', ', array_map(
            static fn (array $period) => $period[0]->setTimezone($timezone)->format('H:i') . '–'
                . $period[1]->setTimezone($timezone)->format('H:i'),
            $periods
        )) : '—';
    }

    private static function hours(int $slots): float
    {
        return round($slots * WorkSchedule::SLOT_MINUTES / 60, 1);
    }
}
