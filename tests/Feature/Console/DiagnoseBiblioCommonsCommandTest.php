<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tpl\Shared\Services\BiblioCommonsTemplateService;

beforeEach(function () {
    Cache::forget('bibliocommons_templates');
    config(['services.bibliocommons.external_templates_url' => null]);
});

test('diagnostic command runs successfully', function () {
    $this->artisan('bibliocommons:diagnose')
        ->assertSuccessful()
        ->expectsOutputToContain('🔍 BiblioCommons Diagnostics');
});

test('diagnostic detects missing api url configuration', function () {
    $this->artisan('bibliocommons:diagnose')
        ->assertSuccessful()
        ->expectsOutputToContain('❌ API URL not configured')
        ->expectsOutputToContain('Add to config/services.php');
});

test('diagnostic shows api url when configured', function () {
    config(['services.bibliocommons.external_templates_url' => 'https://test.bibliocommons.com/api/external-templates']);

    Http::fake([
        '*' => Http::response([
            'css' => '<link>',
            'header' => '<header>Test Header</header>',
            'footer' => '<footer>Test Footer</footer>',
            'screen_reader_navigation' => '<nav>Test</nav>',
            'js' => '<script></script>',
        ], 200),
    ]);

    $this->artisan('bibliocommons:diagnose')
        ->assertSuccessful()
        ->expectsOutputToContain('✓ API URL: https://test.bibliocommons.com/api/external-templates');
});

test('diagnostic shows cache status', function () {
    config(['services.bibliocommons.external_templates_url' => 'https://test.bibliocommons.com/api/external-templates']);

    Http::fake([
        '*' => Http::response([
            'css' => '<link>',
            'header' => '<header>Test</header>',
            'footer' => '<footer>Test</footer>',
            'screen_reader_navigation' => '<nav>Test</nav>',
            'js' => '<script></script>',
        ], 200),
    ]);

    // Cache templates
    $service = app(BiblioCommonsTemplateService::class);
    $service->getTemplateParts();

    $this->artisan('bibliocommons:diagnose')
        ->assertSuccessful()
        ->expectsOutputToContain('✓ Templates are cached');
});

test('diagnostic shows template part sizes', function () {
    config(['services.bibliocommons.external_templates_url' => 'https://test.bibliocommons.com/api/external-templates']);

    Http::fake([
        '*' => Http::response([
            'css' => '<link>',
            'header' => '<header>Test Header</header>',
            'footer' => '<footer>Test Footer</footer>',
            'screen_reader_navigation' => '<nav>Test</nav>',
            'js' => '<script></script>',
        ], 200),
    ]);

    $this->artisan('bibliocommons:diagnose')
        ->assertSuccessful()
        ->expectsOutputToContain('header:')
        ->expectsOutputToContain('footer:')
        ->expectsOutputToContain('css:')
        ->expectsOutputToContain('js:');
});

test('diagnostic detects empty template parts', function () {
    config(['services.bibliocommons.external_templates_url' => 'https://test.bibliocommons.com/api/external-templates']);

    Http::fake([
        '*' => Http::response([
            'css' => '',
            'header' => '',
            'footer' => '',
            'screen_reader_navigation' => '',
            'js' => '',
        ], 200),
    ]);

    $this->artisan('bibliocommons:diagnose')
        ->assertSuccessful()
        ->expectsOutputToContain('❌ All template parts are empty');
});

test('diagnostic provides recommendations', function () {
    $this->artisan('bibliocommons:diagnose')
        ->assertSuccessful()
        ->expectsOutputToContain('💡 Recommendations:')
        ->expectsOutputToContain('📚 For detailed troubleshooting');
});

test('diagnostic shows view composer information', function () {
    $this->artisan('bibliocommons:diagnose')
        ->assertSuccessful()
        ->expectsOutputToContain('📋 View Composer Registration')
        ->expectsOutputToContain('tpl-shared::components.layout')
        ->expectsOutputToContain('tpl-shared::components.static-layout');
});
