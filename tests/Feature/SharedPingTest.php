<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

it('responds ok on tpl-shared ping route', function (): void {
    $response = $this->get('/tpl-shared/ping');

    $response->assertSuccessful();
});

