<?php

namespace App\Support;

/**
 * Read-only inspection of Vite build output (manifest + app bundle).
 */
final class ViteBuildManifestInspector
{
    /**
     * @return array{
     *     manifest_path: string,
     *     manifest_readable: bool,
     *     entry_key: string|null,
     *     js_asset: string,
     *     js_path: string,
     *     js_readable: bool,
     *     resolution: string
     * }
     */
    public function resolveMainJsEntry(): array
    {
        $manifestPath = public_path('build/manifest.json');
        $manifestReadable = is_readable($manifestPath);
        $manifest = $manifestReadable
            ? json_decode((string) file_get_contents($manifestPath), true)
            : [];
        $manifest = is_array($manifest) ? $manifest : [];

        $entryKey = null;
        $jsAsset = '';

        foreach (['resources/js/app.jsx', 'resources/js/app.js', 'resources/js/app.tsx'] as $candidate) {
            $file = (string) data_get($manifest, "{$candidate}.file", '');
            if ($file !== '') {
                $entryKey = $candidate;
                $jsAsset = $file;
                break;
            }
        }

        if ($jsAsset === '') {
            foreach ($manifest as $key => $entry) {
                if (! is_array($entry)) {
                    continue;
                }
                $file = (string) ($entry['file'] ?? '');
                if ($file === '') {
                    continue;
                }
                if (($entry['isEntry'] ?? false) === true && preg_match('#assets/app-[A-Za-z0-9_-]+\.js$#', $file)) {
                    $entryKey = is_string($key) ? $key : null;
                    $jsAsset = $file;
                    break;
                }
            }
        }

        $resolution = 'manifest_entry';
        if ($jsAsset === '') {
            $glob = glob(public_path('build/assets/app-*.js')) ?: [];
            if ($glob !== []) {
                usort($glob, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));
                $jsPath = $glob[0];
                $jsAsset = 'assets/'.basename($jsPath);
                $resolution = 'glob_fallback_newest_app_bundle';
            }
        }

        $jsPath = $jsAsset !== '' ? public_path('build/'.$jsAsset) : '';

        return [
            'manifest_path' => $manifestPath,
            'manifest_readable' => $manifestReadable,
            'entry_key' => $entryKey,
            'js_asset' => $jsAsset,
            'js_path' => $jsPath,
            'js_readable' => $jsPath !== '' && is_readable($jsPath),
            'resolution' => $jsAsset === '' ? 'not_found' : $resolution,
        ];
    }

    public function readMainJsSource(): string
    {
        $resolved = $this->resolveMainJsEntry();
        $path = $resolved['js_path'];

        return ($path !== '' && is_readable($path)) ? (string) file_get_contents($path) : '';
    }
}
