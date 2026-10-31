<?php

return [
    // Days of tracked time a reset fills in before today
    'history_days' => max(0, (int)env('DEMO_HISTORY_DAYS', 14)),

    // Target number of people at work: when fewer would be, team members whose local time is
    // between 07:00 and 22:00 take extra hours. Best effort, it cannot exceed the eligible workers.
    'min_online' => max(0, (int)env('DEMO_MIN_ONLINE', 3)),
];
