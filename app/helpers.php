<?php

if (! function_exists('tplSharedAsset')) {
    /**
     * Resolve the hashed asset filename from the shared package manifest.
     * Usage: tplSharedAsset('css'), tplSharedAsset('js'), etc.
     */
    function tplSharedAsset(string $extension = 'css'): ?string
    {
        $manifestPath = public_path('vendor/tpl-shared/build/manifest.json');
        if (! file_exists($manifestPath)) {
            return null;
        }
        $manifest = json_decode(file_get_contents($manifestPath), true);
        foreach ($manifest as $entry) {
            if (isset($entry['file']) && str_ends_with($entry['file'], '.'.$extension)) {
                return asset('vendor/tpl-shared/build/'.$entry['file']);
            }
        }

        return null;
    }
}
