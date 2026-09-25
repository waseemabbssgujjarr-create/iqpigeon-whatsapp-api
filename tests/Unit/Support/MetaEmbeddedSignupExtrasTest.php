<?php

namespace Tests\Unit\Support;

use App\Support\MetaEmbeddedSignupExtras;
use PHPUnit\Framework\TestCase;

class MetaEmbeddedSignupExtrasTest extends TestCase
{
    public function test_v4_default_extras_are_version_only(): void
    {
        $this->assertSame(['version' => 'v4'], MetaEmbeddedSignupExtras::v4Default());
        $this->assertSame('{"version":"v4"}', MetaEmbeddedSignupExtras::encode(MetaEmbeddedSignupExtras::v4Default()));
    }

    public function test_v4_api_access_only_includes_features(): void
    {
        $extras = MetaEmbeddedSignupExtras::v4ApiAccessOnly();
        $this->assertSame('v4', $extras['version']);
        $this->assertSame(['api_access_only'], $extras['features']);
        $this->assertStringContainsString('api_access_only', MetaEmbeddedSignupExtras::encode($extras));
    }

    public function test_legacy_keys_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MetaEmbeddedSignupExtras::encode([
            'version' => 'v4',
            'featureType' => 'whatsapp_business_app_onboarding',
        ]);
    }

    public function test_embedded_signup_js_does_not_use_legacy_extras(): void
    {
        $path = dirname(__DIR__, 3).'/resources/js/lib/metaEmbeddedSignup.js';
        $source = (string) file_get_contents($path);

        $this->assertStringContainsString('EMBEDDED_SIGNUP_V4_EXTRAS', $source);
        $this->assertStringNotContainsString('featureType', $source);
        $this->assertStringNotContainsString('sessionInfoVersion', $source);
        $this->assertStringNotContainsString('whatsapp_business_app_onboarding', $source);
    }
}
