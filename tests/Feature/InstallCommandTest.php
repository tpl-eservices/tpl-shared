<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up any test files
    if (File::exists(base_path('config/tpl-shared-installed.php'))) {
        File::delete(base_path('config/tpl-shared-installed.php'));
    }
});

afterEach(function () {
    // Clean up test files
    if (File::exists(base_path('config/tpl-shared-installed.php'))) {
        File::delete(base_path('config/tpl-shared-installed.php'));
    }
});

it('registers install command', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('tpl-shared:install');
});

it('registers uninstall command', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('tpl-shared:uninstall');
});

it('install command has correct signature', function () {
    $command = Artisan::all()['tpl-shared:install'];

    expect($command->getName())->toBe('tpl-shared:install');
    expect($command->getDescription())->toContain('BiblioCommons');
});

it('uninstall command has correct signature', function () {
    $command = Artisan::all()['tpl-shared:uninstall'];

    expect($command->getName())->toBe('tpl-shared:uninstall');
    expect($command->getDescription())->toContain('removes configuration');
});

it('handles windows paths correctly when creating backups', function () {
    // Create a test file to backup
    $testConfigDir = base_path('config');
    File::ensureDirectoryExists($testConfigDir);

    $testFile = base_path('config/test-backup.php');
    File::put($testFile, '<?php return [];');

    // Use reflection to test the createBackup method
    $command = new \Tpl\Shared\Console\Commands\InstallTplShared();

    $reflection = new \ReflectionClass($command);
    $method = $reflection->getMethod('createBackup');
    $method->setAccessible(true);

    // Set the timestamp and backupDir properties
    $timestampProperty = $reflection->getProperty('timestamp');
    $timestampProperty->setAccessible(true);
    $timestampProperty->setValue($command, now()->format('Y-m-d_His'));

    $backupDirProperty = $reflection->getProperty('backupDir');
    $backupDirProperty->setAccessible(true);
    $backupDir = storage_path('backups/tpl-shared/test-'.now()->format('Y-m-d_His'));
    $backupDirProperty->setValue($command, $backupDir);

    // Create backup - this should not throw an error even with Windows paths
    $method->invoke($command, $testFile);

    // Verify backup was created
    $relativePath = 'config/test-backup.php';
    $backupPath = $backupDir.'/'.$relativePath;

    expect(File::exists($backupPath))->toBeTrue();

    // Clean up
    File::delete($testFile);
    File::deleteDirectory($backupDir);
});

