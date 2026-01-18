<?php

namespace Tpl\Shared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class TplSharedBuild extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tpl-shared:build {action? : The build action to perform}
                                   {--notes= : Release notes for tagging}
                                   {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'TPL Shared Package Build Management System';

    /**
     * Available actions.
     */
    private array $actions = [
        'help',
        'status',
        'test',
        'format',
        'build',
        'tag-patch',
        'tag-minor',
        'tag-major',
        'push',
        'release',
        'update-version',
        'clean',
        'install',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action') ?? 'help';

        if (! in_array($action, $this->actions)) {
            $this->error("Unknown action: {$action}");
            $this->newLine();
            $this->showHelp();

            return self::FAILURE;
        }

        return match ($action) {
            'help' => $this->showHelp(),
            'status' => $this->showStatus(),
            'test' => $this->runTests(),
            'format' => $this->formatCode(),
            'build' => $this->buildAssets(),
            'tag-patch' => $this->createTag('patch'),
            'tag-minor' => $this->createTag('minor'),
            'tag-major' => $this->createTag('major'),
            'push' => $this->pushToGitHub(),
            'release' => $this->startRelease(),
            'update-version' => $this->updateVersion(),
            'clean' => $this->cleanup(),
            'install' => $this->installDependencies(),
            default => self::FAILURE,
        };
    }

    /**
     * Show help information.
     */
    private function showHelp(): int
    {
        $this->info('TPL Shared Package - Build Management');
        $this->newLine();

        $this->info('Available commands:');
        $this->table(['Command', 'Description'], [
            ['php artisan tpl-shared:build help', 'Show this help information'],
            ['php artisan tpl-shared:build status', 'Show current version and git status'],
            ['php artisan tpl-shared:build test', 'Run tests before releasing'],
            ['php artisan tpl-shared:build format', 'Format code with Laravel Pint'],
            ['php artisan tpl-shared:build build', 'Build frontend assets with Vite'],
            ['', ''],
            ['php artisan tpl-shared:build tag-patch', 'Create a new patch version (0.1.0 -> 0.1.1)'],
            ['php artisan tpl-shared:build tag-minor', 'Create a new minor version (0.1.0 -> 0.2.0)'],
            ['php artisan tpl-shared:build tag-major', 'Create a new major version (0.1.0 -> 1.0.0)'],
            ['', 'Note: Version files are auto-updated on tagging'],
            ['', ''],
            ['php artisan tpl-shared:build push', 'Push commits and tags to GitHub'],
            ['php artisan tpl-shared:build release', 'Full release: test, format, commit, tag-patch, and push'],
            ['', ''],
            ['php artisan tpl-shared:build update-version', 'Manually update composer.json/package.json from latest tag'],
            ['php artisan tpl-shared:build clean', 'Clean up cache and dependencies'],
            ['php artisan tpl-shared:build install', 'Install dependencies'],
        ]);

        $this->newLine();
        $currentTag = $this->getCurrentTag();
        $this->info("Current version: {$currentTag}");

        return self::SUCCESS;
    }

    /**
     * Show current status.
     */
    private function showStatus(): int
    {
        $this->info('=== Current Version ===');
        $currentTag = $this->getCurrentTag();
        $this->line($currentTag);
        $this->newLine();

        $this->info('=== Git Status ===');
        $result = Process::run(['git', 'status', '--short']);
        $this->line($result->output() ?: 'Working directory clean');
        $this->newLine();

        $this->info('=== Recent Commits ===');
        $result = Process::run(['git', 'log', '--oneline', '-5']);
        $this->line($result->output());
        $this->newLine();

        $this->info('=== All Tags ===');
        $result = Process::run(['git', 'tag', '-l']);
        $this->line($result->output() ?: 'No tags found');

        return self::SUCCESS;
    }

    /**
     * Run tests.
     */
    private function runTests(): int
    {
        $this->info('Running tests...');

        $result = Process::run(['composer', 'test']);
        $this->line($result->output());
        $this->error($result->errorOutput());

        if ($result->successful()) {
            $this->info('Tests passed!');

            return self::SUCCESS;
        }

        $this->error('Tests failed!');

        return self::FAILURE;
    }

    /**
     * Format code.
     */
    private function formatCode(): int
    {
        $this->info('Formatting PHP code with Pint...');

        $result = Process::run(['composer', 'format']);
        $this->line($result->output());
        $this->error($result->errorOutput());

        if ($result->successful()) {
            $this->info('Code formatted successfully!');

            return self::SUCCESS;
        }

        $this->error('Formatting failed!');

        return self::FAILURE;
    }

    /**
     * Build frontend assets.
     */
    private function buildAssets(): int
    {
        $this->info('Building frontend assets with Vite...');

        $usePnpm = $this->commandExists('pnpm');
        $command = $usePnpm ? ['pnpm', 'build'] : ['npm', 'run', 'build'];

        $result = Process::run($command);
        $this->line($result->output());
        $this->error($result->errorOutput());

        if ($result->successful()) {
            $this->info('Build complete!');

            return self::SUCCESS;
        }

        $this->error('Build failed!');

        return self::FAILURE;
    }

    /**
     * Create a version tag.
     */
    private function createTag(string $type): int
    {
        $this->info("Creating new {$type} version...");

        // Check if working directory is clean
        $result = Process::run(['git', 'status', '--porcelain']);
        if (! empty(trim($result->output()))) {
            $this->error('Error: Working directory is not clean. Commit or stash changes first.');
            $result = Process::run(['git', 'status', '--short']);
            $this->line($result->output());

            return self::FAILURE;
        }

        $currentTag = $this->getCurrentTag();
        $this->info("Current version: {$currentTag}");

        $newVersion = $this->calculateNewVersion($currentTag, $type);
        $this->info("New version: v{$newVersion}");
        $this->newLine();

        $notes = $this->option('notes') ?? $this->ask('Enter release notes (or press Enter for auto-generated)', "Release v{$newVersion}");
        $this->newLine();

        $this->info('Updating version files...');
        $changesMade = false;

        // Update composer.json
        if (File::exists('composer.json')) {
            $content = File::get('composer.json');
            $content = preg_replace('/"version":\s*"[^"]*"/', "\"version\": \"{$newVersion}\"", $content);
            File::put('composer.json', $content);
            $this->info('  ✓ Updated composer.json');
            $changesMade = true;
        }

        // Update package.json
        if (File::exists('package.json')) {
            $content = File::get('package.json');
            $content = preg_replace('/"version":\s*"[^"]*"/', "\"version\": \"{$newVersion}\"", $content);
            File::put('package.json', $content);
            $this->info('  ✓ Updated package.json');
            $changesMade = true;
        }

        // Commit version updates if changes were made
        if ($changesMade) {
            $this->newLine();
            $this->info('Committing version updates...');
            Process::run(['git', 'add', 'composer.json', 'package.json']);
            Process::run(['git', 'commit', '-m', "Bump version to {$newVersion}"]);
            $this->info('✓ Committed version updates');
            $this->newLine();
        }

        // Create the tag
        $this->info("Creating tag v{$newVersion}...");
        $result = Process::run(['git', 'tag', '-a', "v{$newVersion}", '-m', $notes]);
        if ($result->successful()) {
            $this->info("✓ Created tag v{$newVersion}");
        } else {
            $this->error('Failed to create tag!');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Next steps:');
        $this->line('  1. Run \'php artisan tpl-shared:build push\' to push to GitHub');
        $this->line('  2. Or run \'php artisan tpl-shared:build release\' to do everything automatically');

        return self::SUCCESS;
    }

    /**
     * Push to GitHub.
     */
    private function pushToGitHub(): int
    {
        $this->info('Pushing to GitHub...');

        // Check if working directory is clean
        $result = Process::run(['git', 'status', '--porcelain']);
        if (! empty(trim($result->output()))) {
            $this->error('Error: Uncommitted changes detected. Commit first.');
            $result = Process::run(['git', 'status', '--short']);
            $this->line($result->output());

            return self::FAILURE;
        }

        $this->info('Pushing main branch...');
        $result = Process::run(['git', 'push', 'origin', 'main']);
        if (! $result->successful()) {
            $this->error('Failed to push main branch!');
            $this->error($result->errorOutput());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Pushing tags...');
        $result = Process::run(['git', 'push', 'origin', '--tags']);
        if (! $result->successful()) {
            $this->error('Failed to push tags!');
            $this->error($result->errorOutput());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✓ Successfully pushed to GitHub');
        $this->newLine();

        $latestTag = $this->getCurrentTag();
        $this->info("Latest tag: {$latestTag}");

        return self::SUCCESS;
    }

    /**
     * Start full release workflow.
     */
    private function startRelease(): int
    {
        $this->info('=== Starting Release Process ===');
        $this->newLine();

        $this->info('Step 1: Formatting code...');
        if ($this->formatCode() !== self::SUCCESS) {
            return self::FAILURE;
        }
        $this->newLine();

        // Check if there are PHP/code changes to commit (excluding build artifacts)
        $result = Process::run(['git', 'status', '--porcelain']);
        $statusLines = array_filter(explode("\n", trim($result->output())));
        $nonBuildChanges = array_filter($statusLines, fn ($line) => ! str_contains($line, 'public/build/'));

        if (! empty($nonBuildChanges)) {
            $this->info('Step 2: Committing formatted changes...');
            Process::run(['git', 'add', '--all', '--', ':!public/build/*']);
            Process::run(['git', 'commit', '-m', 'Format code for release']);
            $this->newLine();
        }

        $this->info('Step 3: Building frontend assets...');
        if ($this->buildAssets() !== self::SUCCESS) {
            return self::FAILURE;
        }
        $this->newLine();

        // Check if there are build artifacts to commit
        $result = Process::run(['git', 'status', '--porcelain']);
        if (! empty(trim($result->output()))) {
            $this->info('Step 4: Committing build artifacts...');
            Process::run(['git', 'add', '-A']);
            Process::run(['git', 'commit', '-m', 'Build frontend assets for release']);
            $this->newLine();
        }

        $this->info('Step 5: Creating patch version tag...');
        if ($this->createTag('patch') !== self::SUCCESS) {
            return self::FAILURE;
        }
        $this->newLine();

        $this->info('Step 6: Pushing to GitHub...');
        if ($this->pushToGitHub() !== self::SUCCESS) {
            return self::FAILURE;
        }
        $this->newLine();

        $this->info('🎉 Release complete!');
        $this->newLine();

        $newVersion = $this->getCurrentTag();
        $this->info("New version: {$newVersion}");

        return self::SUCCESS;
    }

    /**
     * Update version from latest tag.
     */
    private function updateVersion(): int
    {
        $currentTag = $this->getCurrentTag();
        if ($currentTag === 'No tags yet') {
            $this->error('Error: No tags found. Create a tag first with \'php artisan tpl-shared:build tag-patch\'.');

            return self::FAILURE;
        }

        $version = ltrim($currentTag, 'v');
        $this->info("Updating version to {$version}...");

        $changesMade = false;

        if (File::exists('composer.json')) {
            $content = File::get('composer.json');
            $content = preg_replace('/"version":\s*"[^"]*"/', "\"version\": \"{$version}\"", $content);
            File::put('composer.json', $content);
            $this->info('  ✓ Updated composer.json');
            $changesMade = true;
        }

        if (File::exists('package.json')) {
            $content = File::get('package.json');
            $content = preg_replace('/"version":\s*"[^"]*"/', "\"version\": \"{$version}\"", $content);
            File::put('package.json', $content);
            $this->info('  ✓ Updated package.json');
            $changesMade = true;
        }

        if ($changesMade) {
            $this->newLine();
            $this->info("Version files updated to {$version}");
            $this->line("Run 'git add -A && git commit -m \"Bump version to {$version}\"' to commit changes");
        }

        return self::SUCCESS;
    }

    /**
     * Clean up dependencies and cache.
     */
    private function cleanup(): int
    {
        $this->info('Cleaning up...');

        if (File::exists('bootstrap/cache')) {
            $files = glob('bootstrap/cache/*.php');
            foreach ($files as $file) {
                File::delete($file);
            }
        }

        if (File::exists('vendor')) {
            File::deleteDirectory('vendor');
        }

        if (File::exists('node_modules')) {
            File::deleteDirectory('node_modules');
        }

        $this->info('✓ Cleaned');

        return self::SUCCESS;
    }

    /**
     * Install dependencies.
     */
    private function installDependencies(): int
    {
        $this->info('Installing dependencies...');

        $result = Process::run(['composer', 'install']);
        $this->line($result->output());
        $this->error($result->errorOutput());

        if (! $result->successful()) {
            $this->error('Composer install failed!');

            return self::FAILURE;
        }

        $usePnpm = $this->commandExists('pnpm');
        $command = $usePnpm ? ['pnpm', 'install'] : ['npm', 'install'];

        $result = Process::run($command);
        $this->line($result->output());
        $this->error($result->errorOutput());

        if (! $result->successful()) {
            $this->error('Package manager install failed!');

            return self::FAILURE;
        }

        $this->info('✓ Dependencies installed');

        return self::SUCCESS;
    }

    /**
     * Get current git tag.
     */
    private function getCurrentTag(): string
    {
        $result = Process::run(['git', 'describe', '--tags', '--abbrev=0']);

        return trim($result->output()) ?: 'No tags yet';
    }

    /**
     * Calculate new version based on type.
     */
    private function calculateNewVersion(string $currentTag, string $type): string
    {
        $version = ltrim($currentTag, 'v');
        $parts = explode('.', $version);

        $major = (int) ($parts[0] ?? 0);
        $minor = (int) ($parts[1] ?? 0);
        $patch = (int) ($parts[2] ?? 0);

        return match ($type) {
            'major' => ($major + 1).'.0.0',
            'minor' => $major.'.'.($minor + 1).'.0',
            default => $major.'.'.$minor.'.'.($patch + 1),
        };
    }

    /**
     * Check if a command exists.
     */
    private function commandExists(string $command): bool
    {
        $result = Process::run(['which', $command]);

        return $result->successful();
    }
}
