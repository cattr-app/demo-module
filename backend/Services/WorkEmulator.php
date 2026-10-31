<?php

namespace Modules\Demo\Services;

use App\Models\Task;
use App\Models\TimeInterval;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Turns the work plan (see PlanWork) into tracked time: one interval per user and five-minute slot.
 *
 * Plan entries are minutes of the day in the application timezone, `end` is exclusive,
 * and an entry with `end` < `start` continues past midnight.
 */
class WorkEmulator
{
    public const SLOT_MINUTES = 5;
    public const PLAN_CACHE_KEY = 'usersPlan';

    /**
     * Create the intervals of the slot that ends at $at (rounded down to the slot boundary)
     * for everyone the plan schedules for it. Slots that already have an interval are skipped,
     * so a slot can be emulated more than once.
     *
     * @return int number of created intervals
     */
    public function emulateSlot(CarbonInterface $at, array $plan): int
    {
        $end = self::slotEnd($at);
        $start = $end->subMinutes(self::SLOT_MINUTES);
        $minute = $start->hour * 60 + $start->minute;

        $created = 0;

        foreach ($plan as $entry) {
            foreach ($entry['intervals'] as $interval) {
                if (!self::covers($interval, $minute)) {
                    continue;
                }

                if ($this->slotIsTaken($entry['user'], $end)
                    || !Task::withoutGlobalScopes()->whereKey($interval['task'])->exists()) {
                    break;
                }

                TimeInterval::factory()->create([
                    'task_id' => $interval['task'],
                    'user_id' => $entry['user'],
                    'start_at' => $start,
                    'end_at' => $end,
                ]);

                $created++;
                break;
            }
        }

        return $created;
    }

    /**
     * Emulate every slot that ends in ($from, $to].
     *
     * @return int number of created intervals
     */
    public function backfill(CarbonInterface $from, CarbonInterface $to, array $plan): int
    {
        $created = 0;

        for ($end = self::slotEnd($from)->addMinutes(self::SLOT_MINUTES); $end <= $to; $end = $end->addMinutes(self::SLOT_MINUTES)) {
            $created += $this->emulateSlot($end, $plan);
        }

        return $created;
    }

    public static function slotEnd(CarbonInterface $at): CarbonImmutable
    {
        $at = CarbonImmutable::instance($at)->startOfMinute();

        return $at->subMinutes($at->minute % self::SLOT_MINUTES);
    }

    /** Whether the plan entry covers the slot starting at the given minute of the day */
    private static function covers(array $interval, int $minute): bool
    {
        ['start' => $start, 'end' => $end] = $interval;

        return $start <= $end
            ? $minute >= $start && $minute < $end
            : $minute >= $start || $minute < $end;
    }

    private function slotIsTaken(int $userId, CarbonImmutable $end): bool
    {
        return TimeInterval::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $end->subMinutes(self::SLOT_MINUTES))
            ->exists();
    }
}
