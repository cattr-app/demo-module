<?php

namespace Modules\Demo\Services;

use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Modules\Demo\Support\DemoAccounts;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Who works when: a working day for every demo worker in their own timezone, and extra hours
 * when too few people would be online.
 *
 * Everything is derived from the worker's email and the date, so the history filled in by a reset
 * and the intervals the scheduler adds later follow the same schedule, without any stored plan.
 */
class WorkSchedule
{
    public const SLOT_MINUTES = 5;
    private const SLOTS_PER_DAY = 24 * 60 / self::SLOT_MINUTES;
    private const CACHED_SLOTS = 48;
    private const CACHED_DAYS = 6;

    private const DAY_OFF_CHANCE = 12;
    private const MEETING_CHANCE = 30;
    private const STAY_ON_TASK_CHANCE = 60;
    /** Local hours [from, to) in which a worker may take extra hours to keep enough people online */
    private const EXTRA_HOURS = [7, 22];

    /** @var array<int, array{user: User, timezone: string, shift: int, profile: string, tasks: int[]}>|null */
    private ?array $workers = null;

    /** @var array<string, array<int, array{task: int, manual: bool}>> */
    private array $days = [];

    /** @var array<int, array<int, array{task: int, manual: bool}>> */
    private array $base = [];

    /**
     * Demo workers with a profile and at least one open task.
     *
     * @return array<int, array{user: User, timezone: string, shift: int, profile: string, tasks: int[]}>
     */
    public function workers(): array
    {
        if ($this->workers !== null) {
            return $this->workers;
        }

        $this->workers = [];

        foreach (DemoAccounts::workers() as $user) {
            $profile = DemoAccounts::workProfile($user->email);

            $tasks = Task::withoutGlobalScopes()
                ->whereHas('users', static fn ($query) => $query->where('users.id', $user->id))
                ->whereHas('status', static fn ($query) => $query->where('active', true))
                ->orderBy('id')
                ->pluck('id')
                ->all();

            if ($profile === null || !$tasks) {
                continue;
            }

            $this->workers[$user->id] = [
                'user' => $user,
                'timezone' => $profile['timezone'] ?? config('app.timezone'),
                'shift' => $profile['shift'],
                'profile' => $profile['profile'],
                'tasks' => $tasks,
            ];
        }

        return $this->workers;
    }

    /**
     * Workers who work in the slot starting at $start, with the task and whether the time is manual.
     *
     * @return array<int, array{task: int, manual: bool}> keyed by user ID
     */
    public function activeAt(CarbonImmutable $start, int $minOnline = 0): array
    {
        $this->prune($start);

        $active = $this->baseAt($start);

        if (count($active) >= $minOnline) {
            return $active;
        }

        // Too few people online: the same workers take the whole hour, so the extra time looks like
        // a block of work rather than scattered slots
        $hour = $start->utc()->startOfHour();
        $busyInHour = [];
        $fewest = PHP_INT_MAX;

        for ($slot = $hour; $slot < $hour->addHour(); $slot = $slot->addMinutes(self::SLOT_MINUTES)) {
            $slotActive = $this->baseAt($slot);
            $busyInHour += $slotActive;
            $fewest = min($fewest, count($slotActive));
        }

        $candidates = array_filter(
            $this->workers(),
            static function (array $worker) use ($hour, $busyInHour) {
                $localHour = $hour->setTimezone($worker['timezone'])->hour;

                return !isset($busyInHour[$worker['user']->id])
                    && $localHour >= self::EXTRA_HOURS[0] && $localHour < self::EXTRA_HOURS[1];
            }
        );

        uasort(
            $candidates,
            static fn (array $a, array $b) => self::hash($a['user']->email, $hour->timestamp)
                <=> self::hash($b['user']->email, $hour->timestamp)
        );

        foreach (array_slice($candidates, 0, $minOnline - $fewest, true) as $userId => $worker) {
            $tasks = $worker['tasks'];
            $active[$userId] = [
                'task' => $tasks[self::hash($worker['user']->email, $hour->timestamp) % count($tasks)],
                'manual' => false,
            ];
        }

        return $active;
    }

    /**
     * Working slots of the worker's local day, keyed by slot number (minute of the day / SLOT_MINUTES).
     *
     * @return array<int, array{task: int, manual: bool}>
     */
    public function day(array $worker, string $date): array
    {
        $key = $worker['user']->id . '|' . $date;

        return $this->days[$key] ??= $this->planDay($worker, $date);
    }

    /**
     * Drop cached slots and days well behind $start, so a long backfill keeps a bounded cache.
     * Callers move forward in time; going back just recomputes the dropped entries.
     */
    private function prune(CarbonImmutable $start): void
    {
        if (count($this->base) > self::CACHED_SLOTS) {
            $border = $start->timestamp - 2 * 3600;
            $this->base = array_filter($this->base, static fn (int $timestamp) => $timestamp >= $border, ARRAY_FILTER_USE_KEY);
        }

        if (count($this->days) > count($this->workers()) * self::CACHED_DAYS) {
            $border = $start->subDays(2)->toDateString();
            $this->days = array_filter(
                $this->days,
                static fn (string $key) => explode('|', $key)[1] >= $border,
                ARRAY_FILTER_USE_KEY
            );
        }
    }

    /** @return array<int, array{task: int, manual: bool}> */
    private function baseAt(CarbonImmutable $start): array
    {
        $timestamp = $start->timestamp;

        if (isset($this->base[$timestamp])) {
            return $this->base[$timestamp];
        }

        $active = [];

        foreach ($this->workers() as $userId => $worker) {
            $local = $start->setTimezone($worker['timezone']);
            $slot = intdiv($local->hour * 60 + $local->minute, self::SLOT_MINUTES);
            $day = $this->day($worker, $local->toDateString());

            if (isset($day[$slot])) {
                $active[$userId] = $day[$slot];
            }
        }

        return $this->base[$timestamp] = $active;
    }

    /** @return array<int, array{task: int, manual: bool}> */
    private function planDay(array $worker, string $date): array
    {
        $random = new Randomizer(new Mt19937(self::hash($worker['user']->email, $date)));

        if ($random->getInt(1, 100) <= self::DAY_OFF_CHANCE) {
            return [];
        }

        $perHour = 60 / self::SLOT_MINUTES;

        // A working day of about nine hours including lunch, starting around the shift hour
        $first = $worker['shift'] * $perHour + $random->getInt(-4, 8);
        $last = min(self::SLOTS_PER_DAY, $first + 9 * $perHour + $random->getInt(-8, 12));

        $breaks = [];
        $lunch = 12 * $perHour + 6 + $random->getInt(0, 12);
        $breaks[] = [$lunch, $lunch + $random->getInt(6, 12)];

        for ($i = $random->getInt(0, 2); $i > 0; $i--) {
            $break = $random->getInt($first + $perHour, max($first + $perHour, $last - $perHour));
            $breaks[] = [$break, $break + $random->getInt(2, 4)];
        }

        $meeting = null;
        if ($random->getInt(1, 100) <= self::MEETING_CHANCE) {
            $start = $random->pickArrayKeys([11 * $perHour => 0, 15 * $perHour => 0], 1)[0];
            $meeting = [$start, $start + 6];
        }

        $tasks = $worker['tasks'];
        $task = $tasks[$random->getInt(0, count($tasks) - 1)];
        $switchAt = $first;
        $day = [];

        for ($slot = $first; $slot < $last; $slot++) {
            foreach ($breaks as [$from, $to]) {
                if ($slot >= $from && $slot < $to) {
                    continue 2;
                }
            }

            if ($slot >= $switchAt) {
                if ($random->getInt(1, 100) > self::STAY_ON_TASK_CHANCE) {
                    $task = $tasks[$random->getInt(0, count($tasks) - 1)];
                }

                $switchAt = $slot + $random->getInt(8, 24);
            }

            $manual = $meeting !== null && $slot >= $meeting[0] && $slot < $meeting[1];
            $day[$slot] = ['task' => $manual ? $tasks[0] : $task, 'manual' => $manual];
        }

        return $day;
    }

    private static function hash(string $email, int|string $salt): int
    {
        return crc32($email . '|' . $salt);
    }
}
