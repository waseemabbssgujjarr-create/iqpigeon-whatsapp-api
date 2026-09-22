<?php

namespace Tests\Unit;

use App\Services\WebhookSignatureService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookSignatureServiceTest extends TestCase
{
    #[Test]
    public function it_signs_and_verifies_partner_webhook_payloads(): void
    {
        $service = new WebhookSignatureService;
        $secret = 'partner_webhook_secret';
        $body = '{"type":"message.received"}';
        $timestamp = '1700000000';

        $signature = $service->sign($secret, $timestamp, $body);

        $this->assertTrue($service->verify($secret, $timestamp, $body, $signature));
        $this->assertFalse($service->verify($secret, $timestamp, $body, 'invalid'));
    }

    #[Test]
    public function it_normalizes_stripe_style_signature_prefixes(): void
    {
        $service = new WebhookSignatureService;
        $secret = 'partner_webhook_secret';
        $body = '{}';
        $timestamp = '1700000000';
        $signature = $service->sign($secret, $timestamp, $body);

        $this->assertTrue($service->verify($secret, $timestamp, $body, 'sha256='.$signature));
    }
}
