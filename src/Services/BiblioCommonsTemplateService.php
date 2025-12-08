<?php

namespace Tpl\Shared\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BiblioCommonsTemplateService
{
    /**
     * Fetch template parts from BiblioCommons API with caching.
     *
     * @return array<string, string>
     */
    public function getTemplateParts(): array
    {
        return Cache::remember('bibliocommons_templates', now()->addHours(24), function () {
            try {
                $apiUrl = config('services.bibliocommons.external_templates_url');

                if (empty($apiUrl)) {
                    Log::warning('BiblioCommons Templates API URL not configured');

                    return $this->getDefaultTemplate();
                }

                $response = Http::timeout(10)->get($apiUrl);

                if ($response->failed()) {
                    Log::warning('BiblioCommons Templates API request failed', [
                        'status' => $response->status(),
                        'url' => $apiUrl,
                    ]);

                    return $this->getDefaultTemplate();
                }

                $data = $response->json();

                return [
                    'css' => $data['css'] ?? '',
                    'screen_reader_navigation' => $data['screen_reader_navigation'] ?? '',
                    'header' => $data['header'] ?? '',
                    'footer' => $data['footer'] ?? '',
                    'js' => $data['js'] ?? '',
                ];
            } catch (\Exception $e) {
                Log::error('BiblioCommons API error', [
                    'message' => $e->getMessage(),
                ]);

                return $this->getDefaultTemplate();
            }
        });
    }

    /**
     * Get default empty template when API fails.
     *
     * @return array<string, string>
     */
    protected function getDefaultTemplate(): array
    {
        return [
            'css' => '',
            'screen_reader_navigation' => '',
            'header' => '',
            'footer' => '',
            'js' => '',
        ];
    }

    /**
     * Clear the cached template parts.
     */
    public function clearCache(): void
    {
        Cache::forget('bibliocommons_templates');
    }
}
