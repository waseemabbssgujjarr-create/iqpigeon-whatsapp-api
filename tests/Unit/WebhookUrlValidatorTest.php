<?php

namespace Tests\Unit;

use App\Support\WebhookUrlValidator;
use Tests\TestCase;

class WebhookUrlValidatorTest extends TestCase
{
    public function test_rejects_localhost_and_private_ips(): void
    {
        $validator = new WebhookUrlValidator;

        $this->assertFalse($validator->isAllowed('http://127.0.0.1/hook'));
        $this->assertFalse($validator->isAllowed('https://localhost/callback'));
        $this->assertFalse($validator->isAllowed('https://10.0.0.5/internal'));
        $this->assertFalse($validator->isAllowed('https://169.254.169.254/latest/meta-data'));
        $this->assertFalse($validator->isAllowed('https://partner.internal/hooks'));
    }

    public function test_allows_public_https_urls(): void
    {
        $validator = new WebhookUrlValidator;

        $this->assertTrue($validator->isAllowed('https://example.com/webhooks/iqpigeon'));
    }
}
