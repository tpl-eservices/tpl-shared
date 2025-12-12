<?php

namespace Tpl\Shared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UninstallTplShared extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tpl-shared:uninstall {--dry-run : Show what would be removed without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Uninstall TPL Shared package - removes configuration and restores backups';

    /**
     * Files to be modified.
     */
    protected array $filesToModify = [];

    /**
     * Removal status.
     */
    protected array $status = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->warn('TPL Shared Package Uninstallation');
        $this->newLine();

        // Check if installed
        if (! $this->isInstalled()) {
            $this->components->info('TPL Shared is not installed or installation file not found.');

            return Command::SUCCESS;
        }

        // Get installation info
        $installInfo = $this->getInstallationInfo();

        // Show what will be done
        $this->showUninstallPlan($installInfo);

        if ($this->option('dry-run')) {
            $this->components->info('Dry run - no changes made');

            return Command::SUCCESS;
        }

        // Confirm uninstallation
        if (! $this->components->confirm('Are you sure you want to uninstall TPL Shared?', false)) {
            $this->components->info('Uninstallation cancelled.');

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->components->info('Starting uninstallation...');
        $this->newLine();

        // Create final backup before uninstall
        $finalBackupDir = $this->createFinalBackup();

        // Step 1: Remove from config/services.php
        $this->removeFromServicesConfig();

        // Step 2: Remove from config/auth.php
        $this->removeFromAuthConfig();

        // Step 3: Remove from bootstrap/app.php
        $this->removeFromBootstrapApp();

        // Step 4: Ask about .env cleanup
        $this->cleanupEnvFile();

        // Step 5: Remove installation status file
        $this->removeInstallationStatus();

        // Show summary
        $this->newLine();
        $this->showUninstallSummary($finalBackupDir);

        return Command::SUCCESS;
    }

    /**
     * Check if package is installed.
     */
    protected function isInstalled(): bool
    {
        return File::exists(base_path('config/tpl-shared-installed.php'));
    }

    /**
     * Get installation information.
     */
    protected function getInstallationInfo(): array
    {
        $statusFile = base_path('config/tpl-shared-installed.php');

        if (! File::exists($statusFile)) {
            return [];
        }

        return require $statusFile;
    }

    /**
     * Show uninstall plan.
     */
    protected function showUninstallPlan(array $installInfo): void
    {
        $this->components->info('The following changes will be made:');
        $this->newLine();

        $actions = [
            ['config/services.php', 'Remove BiblioCommons configuration'],
            ['config/auth.php', 'Remove biblio guard and provider'],
            ['bootstrap/app.php', 'Remove middleware alias'],
            ['.env', 'Remove BiblioCommons variables (with confirmation)'],
            ['config/tpl-shared-installed.php', 'Remove installation status file'],
        ];

        $this->table(['File', 'Action'], $actions);

        $this->newLine();
        $this->components->warn('Manual cleanup required for:');
        $this->line('  • app/Models/User.php - Stateless authentication properties');
        $this->line('  • resources/views/vendor/tpl-shared - Published views');
        $this->line('  • .env.tpl-shared.example - Example environment file');

        if (isset($installInfo['backup_location'])) {
            $this->newLine();
            $this->components->info('Original backups available at:');
            $this->line("  {$installInfo['backup_location']}");
        }

        $this->newLine();
    }

    /**
     * Create final backup before uninstall.
     */
    protected function createFinalBackup(): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $backupDir = storage_path("backups/tpl-shared/uninstall-{$timestamp}");

        File::ensureDirectoryExists($backupDir);

        $filesToBackup = [
            'config/services.php',
            'config/auth.php',
            'bootstrap/app.php',
            '.env',
        ];

        foreach ($filesToBackup as $file) {
            $filePath = base_path($file);
            if (File::exists($filePath)) {
                $backupPath = $backupDir.'/'.$file;
                File::ensureDirectoryExists(dirname($backupPath));
                File::copy($filePath, $backupPath);
            }
        }

        return $backupDir;
    }

    /**
     * Remove BiblioCommons config from config/services.php.
     */
    protected function removeFromServicesConfig(): void
    {
        $configFile = base_path('config/services.php');

        $this->components->task('Removing from config/services.php', function () use ($configFile) {
            if (! File::exists($configFile)) {
                $this->status['services_config'] = 'not_found';

                return false;
            }

            $content = File::get($configFile);

            // Remove BiblioCommons configuration block
            $pattern = '/\n\s*\/\/ TPL Shared - BiblioCommons Configuration.*?\'bibliocommons\'\s*=>\s*\[.*?\],\n/s';
            $newContent = preg_replace($pattern, '', $content);

            if ($newContent !== $content) {
                File::put($configFile, $newContent);
                $this->status['services_config'] = 'removed';

                return true;
            }

            $this->status['services_config'] = 'not_found';

            return true;
        });
    }

    /**
     * Remove BiblioCommons config from config/auth.php.
     */
    protected function removeFromAuthConfig(): void
    {
        $configFile = base_path('config/auth.php');

        $this->components->task('Removing from config/auth.php', function () use ($configFile) {
            if (! File::exists($configFile)) {
                $this->status['auth_config'] = 'not_found';

                return false;
            }

            $content = File::get($configFile);
            $modified = false;

            // Remove guard
            $guardPattern = '/\n\s*\/\/ TPL Shared - BiblioCommons Guard.*?\'biblio\'\s*=>\s*\[.*?\],\n/s';
            $newContent = preg_replace($guardPattern, '', $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;
            }

            // Remove provider
            $providerPattern = '/\n\s*\/\/ TPL Shared - BiblioCommons Provider.*?\'biblio\'\s*=>\s*\[.*?\],\n/s';
            $newContent = preg_replace($providerPattern, '', $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;
            }

            if ($modified) {
                File::put($configFile, $content);
                $this->status['auth_config'] = 'removed';

                return true;
            }

            $this->status['auth_config'] = 'not_found';

            return true;
        });
    }

    /**
     * Remove middleware alias from bootstrap/app.php.
     */
    protected function removeFromBootstrapApp(): void
    {
        $bootstrapFile = base_path('bootstrap/app.php');

        $this->components->task('Removing from bootstrap/app.php', function () use ($bootstrapFile) {
            if (! File::exists($bootstrapFile)) {
                $this->status['bootstrap_app'] = 'not_found';

                return false;
            }

            $content = File::get($bootstrapFile);

            // Remove entire middleware alias block if it only contains biblio.auth
            $blockPattern = '/\n\s*\/\/ TPL Shared - BiblioCommons Middleware\s*\n\s*\$middleware->alias\(\[\s*\'biblio\.auth\'\s*=>\s*\\\\Tpl\\\\Shared\\\\Http\\\\Middleware\\\\AuthenticateBiblioCommons::class,\s*\]\);\n/s';
            $newContent = preg_replace($blockPattern, '', $content);

            if ($newContent !== $content) {
                File::put($bootstrapFile, $newContent);
                $this->status['bootstrap_app'] = 'removed';

                return true;
            }

            // Try to remove just the biblio.auth line from existing alias array
            $linePattern = '/\s*\'biblio\.auth\'\s*=>\s*\\\\Tpl\\\\Shared\\\\Http\\\\Middleware\\\\AuthenticateBiblioCommons::class,\n/';
            $newContent = preg_replace($linePattern, '', $content);

            if ($newContent !== $content) {
                File::put($bootstrapFile, $newContent);
                $this->status['bootstrap_app'] = 'removed';

                return true;
            }

            $this->status['bootstrap_app'] = 'not_found';

            return true;
        });
    }

    /**
     * Cleanup .env file.
     */
    protected function cleanupEnvFile(): void
    {
        $envFile = base_path('.env');

        if (! File::exists($envFile)) {
            $this->status['env_file'] = 'not_found';

            return;
        }

        $this->newLine();
        if (! $this->components->confirm('Remove BiblioCommons variables from .env file?', false)) {
            $this->status['env_file'] = 'skipped';

            return;
        }

        $this->components->task('Removing from .env file', function () use ($envFile) {
            $content = File::get($envFile);

            // Remove BiblioCommons section
            $pattern = '/\n# TPL Shared - BiblioCommons Configuration.*?BIBLIO_SESSION_COOKIE=.*?\n/s';
            $newContent = preg_replace($pattern, "\n", $content);

            if ($newContent !== $content) {
                File::put($envFile, $newContent);
                $this->status['env_file'] = 'removed';

                return true;
            }

            $this->status['env_file'] = 'not_found';

            return true;
        });
    }

    /**
     * Remove installation status file.
     */
    protected function removeInstallationStatus(): void
    {
        $statusFile = base_path('config/tpl-shared-installed.php');

        $this->components->task('Removing installation status file', function () use ($statusFile) {
            if (File::exists($statusFile)) {
                File::delete($statusFile);
                $this->status['status_file'] = 'removed';

                return true;
            }

            $this->status['status_file'] = 'not_found';

            return true;
        });
    }

    /**
     * Show uninstall summary.
     */
    protected function showUninstallSummary(string $backupDir): void
    {
        $this->components->info('Uninstallation Summary');
        $this->newLine();

        $this->table(
            ['Component', 'Status'],
            [
                ['config/services.php', $this->formatStatus($this->status['services_config'] ?? 'unknown')],
                ['config/auth.php', $this->formatStatus($this->status['auth_config'] ?? 'unknown')],
                ['bootstrap/app.php', $this->formatStatus($this->status['bootstrap_app'] ?? 'unknown')],
                ['.env', $this->formatStatus($this->status['env_file'] ?? 'skipped')],
                ['Installation status', $this->formatStatus($this->status['status_file'] ?? 'unknown')],
            ]
        );

        $this->newLine();
        $this->components->info('Final backup created at:');
        $this->line("  {$backupDir}");

        $this->newLine();
        $this->components->warn('Manual cleanup required:');
        $this->newLine();

        $this->line('  1. User Model Properties:');
        $this->line('     Remove stateless authentication properties from app/Models/User.php');
        $this->line('     Look for: // TPL Shared - Stateless Authentication Properties');
        $this->newLine();

        $this->line('  2. Published Views:');
        $this->line('     Delete: resources/views/vendor/tpl-shared/');
        $this->line('     Command: rm -rf resources/views/vendor/tpl-shared');
        $this->newLine();

        $this->line('  3. Example Files:');
        $this->line('     Delete: .env.tpl-shared.example');
        $this->line('     Delete: config/examples/tpl-shared/ (if exists)');
        $this->newLine();

        $this->line('  4. Published Assets:');
        $this->line('     Delete: public/vendor/tpl-shared/ (if exists)');
        $this->newLine();

        $this->components->info('To restore from backup:');
        $this->line("  cp {$backupDir}/config/services.php config/services.php");
        $this->line("  cp {$backupDir}/config/auth.php config/auth.php");
        $this->line("  cp {$backupDir}/bootstrap/app.php bootstrap/app.php");

        $this->newLine();
        $this->components->info('✅ TPL Shared uninstalled successfully!');
    }

    /**
     * Format status for display.
     */
    protected function formatStatus(string $status): string
    {
        return match ($status) {
            'removed' => '✅ Removed',
            'not_found' => '⏭️ Not found',
            'skipped' => '⏭️ Skipped',
            default => '❓ Unknown',
        };
    }
}
