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

    public function test_business_app_coexistence_oauth_extras_match_js_launcher(): void
    {
        $encoded = MetaEmbeddedSignupExtras::encodeBusinessAppCoexistence();

        $this->assertStringContainsString('whatsapp_business_app_onboarding', $encoded);
        $this->assertStringContainsString('sessionInfoVersion', $encoded);
        $this->assertStringContainsString('"3"', $encoded);
        $this->assertStringNotContainsString('"version"', $encoded);
    }

    public function test_coexistence_embedded_signup_js_extras_match_meta_business_app_flow(): void
    {
        $path = dirname(__DIR__, 3).'/resources/js/lib/metaEmbeddedSignup.js';
        $source = (string) file_get_contents($path);

        $this->assertStringContainsString('COEXISTENCE_EMBEDDED_SIGNUP_EXTRAS', $source);
        $this->assertStringContainsString('whatsapp_business_app_onboarding', $source);
        $this->assertStringContainsString("sessionInfoVersion: '3'", $source);
        $this->assertStringNotContainsString("'api_access_only'", $source);
        $this->assertStringNotContainsString("version: 'v4'", $source);
        $this->assertStringContainsString('fallback_redirect_uri', $source);
    }
}
