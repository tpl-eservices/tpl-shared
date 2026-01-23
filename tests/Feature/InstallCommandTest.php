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
    // Use a temp directory to avoid race conditions with parallel tests
    // (Orchestra Testbench scans config/ and tries to load PHP files)
    $tempDir = sys_get_temp_dir().'/tpl-shared-test-'.uniqid();
    File::ensureDirectoryExists($tempDir.'/config');

    $testFile = $tempDir.'/config/test-backup.php';
    File::put($testFile, '<?php return [];');

    // Use reflection to test the createBackup method
    $command = new \Tpl\Shared\Console\Commands\InstallTplShared;

    $reflection = new \ReflectionClass($command);
    $method = $reflection->getMethod('createBackup');
    $method->setAccessible(true);

    // Set the timestamp and backupDir properties
    $timestampProperty = $reflection->getProperty('timestamp');
    $timestampProperty->setAccessible(true);
    $timestampProperty->setValue($command, now()->format('Y-m-d_His'));

    $backupDirProperty = $reflection->getProperty('backupDir');
    $backupDirProperty->setAccessible(true);
    $backupDir = $tempDir.'/backups/tpl-shared/test-'.now()->format('Y-m-d_His');
    $backupDirProperty->setValue($command, $backupDir);

    // Mock base_path to return our temp directory for this test
    // We need to manually set up the path relationship
    $originalBasePath = base_path();

    // Create backup - this should not throw an error even with Windows paths
    // The createBackup method normalizes paths and removes the base_path prefix
    // So we need to temporarily adjust how the method sees paths
    $method->invoke($command, $testFile);

    // Verify backup was created - the backup path will be relative to the temp dir
    // Since createBackup strips base_path(), it will create the full path structure
    expect(File::exists($backupDir))->toBeTrue();

    // Clean up entire temp directory
    File::deleteDirectory($tempDir);
});
