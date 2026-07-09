<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Tpl\Shared\Services\BiblioSsoService;
use Tpl\Shared\Services\FakeBiblioSsoService;

it('registers blade component namespace with correct single backslashes', function () {
    $namespaces = Blade::getClassComponentNamespaces();

    expect($namespaces)->toHaveKey('tpl-shared');

    // The namespace must use single backslashes, not double
    $namespace = $namespaces['tpl-shared'];
    expect($namespace)->toBe('Tpl\\Shared\\View\\Components');
    expect($namespace)->not->toContain('\\\\');
});

it('allows mock BiblioCommons in staging environment', function () {
    config()->set('services.bibliocommons.mock_enabled', true);
    config()->set('services.bibliocommons.api_key', 'test-key');

    app()->detectEnvironment(fn () => 'staging');

    // Expect the warning log (mock mode enabled), not the critical log (blocked)
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($msg) => str_contains($msg, 'mock mode enabled'));
    Log::shouldReceive('critical')->never();

    app()->forgetInstance(BiblioSsoService::class);
    $service = app()->make(BiblioSsoService::class);

    expect($service)->toBeInstanceOf(FakeBiblioSsoService::class);
});
