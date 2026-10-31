<?php

namespace Modules\Demo\Commands;

use Illuminate\Console\Command;
use Modules\Demo\Services\WorkEmulator;

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

    public function handle(WorkEmulator $emulator): int
    {
        // A reset is in progress; it fills in the time so far by itself
        if ($this->laravel->isDownForMaintenance()) {
            return 0;
        }

        $emulator->emulateSlot(now());

        return 0;
    }
}
