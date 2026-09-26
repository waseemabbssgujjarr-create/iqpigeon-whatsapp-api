<?php

namespace Tests\Unit\Support;

use App\Support\ViteBuildManifestInspector;
use Tests\TestCase;

class ViteBuildManifestInspectorTest extends TestCase
{
    public function test_resolves_js_entry_from_manifest_key(): void
    {
        $buildDir = public_path('build');
        if (! is_dir($buildDir)) {
            mkdir($buildDir, 0777, true);
        }
        $assetsDir = $buildDir.'/assets';
        if (! is_dir($assetsDir)) {
            mkdir($assetsDir, 0777, true);
        }

        $manifest = [
            'resources/js/app.jsx' => [
                'file' => 'assets/app-testprobe.js',
                'isEntry' => true,
            ],
        ];
        file_put_contents($buildDir.'/manifest.json', json_encode($manifest));
        file_put_contents($assetsDir.'/app-testprobe.js', 'featureType whatsapp_business_app_onboarding');

        $resolved = (new ViteBuildManifestInspector)->resolveMainJsEntry();

        $this->assertSame('assets/app-testprobe.js', $resolved['js_asset']);
        $this->assertSame('manifest_entry', $resolved['resolution']);
        $this->assertTrue($resolved['js_readable']);
    }
}
