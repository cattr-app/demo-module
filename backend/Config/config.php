<?php

return [
    // Days of tracked time a reset fills in before today
    'history_days' => (int)env('DEMO_HISTORY_DAYS', 14),

    // The emulator gives extra hours to team members when fewer people would be at work
    'min_online' => (int)env('DEMO_MIN_ONLINE', 3),
];
