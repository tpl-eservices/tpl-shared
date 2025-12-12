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
