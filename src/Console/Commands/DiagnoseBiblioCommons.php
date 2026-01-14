<?php

namespace Tpl\Shared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Tpl\Shared\Services\BiblioCommonsTemplateService;

class DiagnoseBiblioCommons extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bibliocommons:diagnose';

    /**
     * The console command description.
     */
    protected $description = 'Diagnose BiblioCommons configuration and connection';

    /**
     * Execute the console command.
     */
    public function handle(BiblioCommonsTemplateService $service): int
    {
        $this->info('🔍 BiblioCommons Diagnostics');
        $this->newLine();

        // Check 1: Configuration
        $this->components->task('Checking configuration', function () {
            $apiUrl = config('services.bibliocommons.external_templates_url');

            if (empty($apiUrl)) {
                $this->error('  ❌ API URL not configured');
                $this->warn('  ➜ Add to config/services.php:');
                $this->line("     'bibliocommons' => ['external_templates_url' => env('BIBLIOCOMMONS_API_URL')]");
                $this->warn('  ➜ Add to .env:');
                $this->line('     BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates');

                return false;
            }

            $this->info("  ✓ API URL: {$apiUrl}");

            return true;
        });

        // Check 2: Cache status
        $cached = Cache::has('bibliocommons_templates');
        if ($cached) {
            $this->components->task('Checking cache', function () {
                $this->info('  ✓ Templates are cached');
                $cacheData = Cache::get('bibliocommons_templates');
                $this->info('  ✓ Cache contains: '.implode(', ', array_keys($cacheData)));

                return true;
            });
        } else {
            $this->components->warn('  ⚠ Templates not cached (will fetch from API)');
        }

        // Check 3: Fetch templates
        $this->newLine();
        $this->components->task('Fetching template parts', function () use ($service) {
            try {
                $parts = $service->getTemplateParts();

                $hasContent = false;
                foreach (['header', 'footer', 'css', 'js'] as $key) {
                    if (! empty($parts[$key])) {
                        $length = strlen($parts[$key]);
                        $this->info("  ✓ {$key}: {$length} bytes");
                        $hasContent = true;
                    } else {
                        $this->warn("  ⚠ {$key}: empty");
                    }
                }

                if (! $hasContent) {
                    $this->error('  ❌ All template parts are empty');
                    $this->warn('  ➜ Check API URL and network connectivity');
                    $this->warn('  ➜ Check storage/logs/laravel.log for errors');

                    return false;
                }

                return true;
            } catch (\Exception $e) {
                $this->error("  ❌ Error: {$e->getMessage()}");

                return false;
            }
        });

        // Check 4: View composer registration
        $this->newLine();
        $this->info('📋 View Composer Registration:');
        $this->line('  The package automatically registers the BiblioCommons composer for:');
        $this->line('  - tpl-shared::components.layout');
        $this->line('  - tpl-shared::components.static-layout');
        $this->line('  - tpl-shared::components.inertia-layout');
        $this->newLine();
        $this->warn('  ⚠ Do NOT register the composer in your AppServiceProvider');
        $this->warn('    if using <x-tpl-shared::layout> or <x-tpl-shared::static-layout>');

        // Recommendations
        $this->newLine();
        $this->info('💡 Recommendations:');

        if (! config('services.bibliocommons.external_templates_url')) {
            $this->line('  1. Configure BiblioCommons API URL (see above)');
            $this->line('  2. Run: php artisan config:clear');
            $this->line('  3. Run this command again');
        } else {
            $this->line('  1. Use package views: <x-tpl-shared::static-layout>');
            $this->line('  2. Remove View::composer() from your AppServiceProvider');
            $this->line('  3. Clear caches: php artisan optimize:clear');
            $this->line('  4. Test your application');
        }

        $this->newLine();
        $this->info('📚 For detailed troubleshooting, see:');
        $this->line('   - FIX_HOST_APP_SETUP.md');
        $this->line('   - TROUBLESHOOTING_HOST_APP.md');

        return self::SUCCESS;
    }
}

