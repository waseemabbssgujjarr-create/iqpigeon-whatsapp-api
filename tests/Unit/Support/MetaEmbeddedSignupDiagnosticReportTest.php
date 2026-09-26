<?php

namespace Tests\Unit\Support;

use App\Support\MetaEmbeddedSignupDiagnosticReport;
use App\Support\MetaEmbeddedSignupExtras;
use Tests\TestCase;

class MetaEmbeddedSignupDiagnosticReportTest extends TestCase
{
    public function test_report_includes_passing_encoder_check_when_extras_match(): void
    {
        config([
            'services.meta.app_id' => '552479924130015',
            'services.meta.es_config_id' => '1647730086942089',
            'services.meta.es_config_id_coexistence' => '97624893834457',
            'services.meta.graph_version' => 'v25.0',
        ]);

        $report = (new MetaEmbeddedSignupDiagnosticReport)->build(null);

        $encoderRow = collect($report['summary'])->firstWhere('check', 'Extras encoder');
        $this->assertNotNull($encoderRow);
        $this->assertSame('PASS', $encoderRow['status']);

        $json = MetaEmbeddedSignupExtras::encodeBusinessAppCoexistence();
        $this->assertStringContainsString('sessionInfoVersion', $json);
        $this->assertStringNotContainsString('"version"', $json);
    }

    public function test_diagnostic_route_requires_authentication(): void
    {
        $response = $this->get(route('debug.meta-embedded-signup'));

        $response->assertRedirect();
    }
}
