<?php

namespace Tests\Unit;

use App\Services\Meta\MetaGraphErrorMapper;
use PHPUnit\Framework\TestCase;

class MetaGraphErrorMapperTest extends TestCase
{
    public function test_maps_display_name_error_131037(): void
    {
        $mapper = new MetaGraphErrorMapper();
        $mapped = $mapper->mapSendFailure(400, [
            'error' => [
                'code' => 131037,
                'message' => 'WhatsApp provided number needs display name approval before message can be sent.',
            ],
        ]);

        $this->assertSame('display_name_not_approved', $mapped['failure_code']);
        $this->assertStringContainsString('display name', strtolower($mapped['failure_message']));
        $this->assertSame('131037', $mapped['provider_code']);
    }

    public function test_maps_template_not_found_132001(): void
    {
        $mapper = new MetaGraphErrorMapper();
        $mapped = $mapper->mapSendFailure(404, [
            'error' => ['code' => 132001, 'message' => 'Template name does not exist in the translation'],
        ]);

        $this->assertSame('template_not_found', $mapped['failure_code']);
        $this->assertStringContainsString('hello_world', $mapped['failure_message']);
    }
}
