<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/tpl-shared/ping', fn () => 'ok')->name('tpl-shared.ping');
});

