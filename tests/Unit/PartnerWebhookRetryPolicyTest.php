<?php

namespace Tests\Unit;

use App\Services\PartnerWebhookRetryPolicy;
use Illuminate\Http\Client\ConnectionException;
use Tests\TestCase;

class PartnerWebhookRetryPolicyTest extends TestCase
{
    public function test_retryable_status_codes(): void
    {
        $policy = new PartnerWebhookRetryPolicy;

        $this->assertTrue($policy->shouldRetryHttpStatus(408));
        $this->assertTrue($policy->shouldRetryHttpStatus(429));
        $this->assertTrue($policy->shouldRetryHttpStatus(500));
        $this->assertFalse($policy->shouldRetryHttpStatus(400));
        $this->assertFalse($policy->shouldRetryHttpStatus(404));
    }

    public function test_connection_exception_is_retryable(): void
    {
        $policy = new PartnerWebhookRetryPolicy;

        $this->assertTrue($policy->shouldRetryThrowable(new ConnectionException('timeout')));
    }
}
