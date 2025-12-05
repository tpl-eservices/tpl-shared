<?php

namespace Tpl\Shared\Console\Commands;

use Illuminate\Console\Command;
use Tpl\Shared\Services\BiblioCommonsTemplateService;

class ClearBiblioCommonsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bibliocommons:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the cached BiblioCommons template parts';

    /**
     * Execute the console command.
     */
    public function handle(BiblioCommonsTemplateService $service): int
    {
        $service->clearCache();

        $this->components->info('BiblioCommons template cache cleared successfully.');

        return Command::SUCCESS;
    }
}
