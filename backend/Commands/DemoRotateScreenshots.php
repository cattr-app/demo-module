<?php

namespace Modules\Demo\Commands;

use App\Console\Commands\RotateScreenshots;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Replaces the core rotation under the same name, so neither the weekly schedule nor a manual
 * run can remove anything: the demo disk is not measured and holds only the shared placeholders.
 */
#[AsCommand(name: 'cattr:screenshots:rotate')]
class DemoRotateScreenshots extends RotateScreenshots
{
    public function handle(): void
    {
        $this->info('Screenshot rotation is disabled in the demo');
    }
}
