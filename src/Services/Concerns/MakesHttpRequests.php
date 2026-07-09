<?php

namespace Tpl\Shared\Services\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

trait MakesHttpRequests
{
    /**
     * Create an HTTP client with optional headers and SSL verification handling.
     *
     * In non-production environments, SSL verification is disabled to support
     * self-signed certificates on staging servers.
     *
     * @param  array<string, string>  $headers
     */
    protected function httpClient(array $headers = []): PendingRequest
    {
        $client = Http::withHeaders($headers)->timeout(30);

        if (! app()->environment('production')) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }
}
