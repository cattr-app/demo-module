<?php

namespace Modules\Demo\Services;

use App\Models\TimeInterval;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Modules\Demo\Support\DemoCatalog;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Turns the work schedule (see WorkSchedule) into tracked time: one interval per worker and
 * five-minute slot, keeping at least `demo.min_online` people at work.
 */
class WorkEmulator
{
    public const SLOT_MINUTES = WorkSchedule::SLOT_MINUTES;
    private const INSERT_CHUNK = 1000;

    public function __construct(private readonly WorkSchedule $schedule)
    {
    }

    /**
     * Create the intervals of the slot that ends at $at (rounded down to the slot boundary).
     * Workers who already have time in the slot are skipped, so a slot can be emulated more than once.
     *
     * @return int number of created intervals
     */
    public function emulateSlot(CarbonInterface $at): int
    {
        $end = self::slotEnd($at);
        $start = $end->subMinutes(self::SLOT_MINUTES);
        $created = 0;

        foreach ($this->schedule->activeAt($start, $this->minOnline()) as $userId => $work) {
            if ($this->slotIsTaken($userId, $start, $end)) {
                continue;
            }

            TimeInterval::factory()->create($this->interval($userId, $work, $start, $end));
            User::whereKey($userId)->update(['last_activity' => $end]);

            $created++;
        }

        return $created;
    }

    /**
     * Emulate every slot that ends in ($from, $to] with bulk inserts, bypassing model events.
     * Meant for a freshly reset database: existing intervals are not checked, and the reset
     * rebuilds the tracked time totals afterwards (cattr:task:recreate-workers).
     *
     * @return int number of created intervals
     */
    public function backfill(CarbonInterface $from, CarbonInterface $to): int
    {
        $rows = [];
        $lastActivity = [];
        $created = 0;
        $now = now()->toDateTimeString();
        $minOnline = $this->minOnline();

        for ($end = self::slotEnd($from)->addMinutes(self::SLOT_MINUTES); $end <= $to; $end = $end->addMinutes(self::SLOT_MINUTES)) {
            $start = $end->subMinutes(self::SLOT_MINUTES);

            foreach ($this->schedule->activeAt($start, $minOnline) as $userId => $work) {
                $interval = $this->interval($userId, $work, $start, $end);
                $interval['start_at'] = $interval['start_at']->toDateTimeString();
                $interval['end_at'] = $interval['end_at']->toDateTimeString();
                $rows[] = $interval + ['created_at' => $now, 'updated_at' => $now];
                $lastActivity[$userId] = $end;
            }

            if (count($rows) >= self::INSERT_CHUNK) {
                $created += $this->insert($rows);
            }
        }

        $created += $this->insert($rows);

        foreach ($lastActivity as $userId => $at) {
            User::whereKey($userId)->update(['last_activity' => $at]);
        }

        return $created;
    }

    public static function slotEnd(CarbonInterface $at): CarbonImmutable
    {
        $at = CarbonImmutable::instance($at)->startOfMinute();

        return $at->subMinutes($at->minute % self::SLOT_MINUTES);
    }

    /** @param array{task: int, manual: bool} $work */
    private function interval(int $userId, array $work, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $interval = [
            'task_id' => $work['task'],
            'user_id' => $userId,
            'start_at' => $start,
            'end_at' => $end,
            'is_manual' => $work['manual'],
            'mouse_fill' => null,
            'keyboard_fill' => null,
            'activity_fill' => null,
        ];

        if ($work['manual']) {
            return $interval;
        }

        $worker = $this->schedule->workers()[$userId];
        $profile = DemoCatalog::ACTIVITY_PROFILES[$worker['profile']];
        $random = new Randomizer(new Mt19937(crc32($userId . '|' . $start->timestamp)));

        $interval['keyboard_fill'] = $random->getInt(...$profile['keyboard']);
        $interval['mouse_fill'] = $random->getInt(...$profile['mouse']);
        $interval['activity_fill'] = intdiv($interval['keyboard_fill'] + $interval['mouse_fill'], 2);

        return $interval;
    }

    private function insert(array &$rows): int
    {
        $count = count($rows);

        if ($count) {
            TimeInterval::insert($rows);
            $rows = [];
        }

        return $count;
    }

    private function slotIsTaken(int $userId, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return TimeInterval::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->exists();
    }

    private function minOnline(): int
    {
        return (int)config('demo.min_online');
    }
}
