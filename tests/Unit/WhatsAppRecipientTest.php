<?php

namespace Tests\Unit;

use App\Support\WhatsAppRecipient;
use PHPUnit\Framework\TestCase;

class WhatsAppRecipientTest extends TestCase
{
    public function test_normalize_strips_plus_and_non_digits(): void
    {
        $this->assertSame('923004522663', WhatsAppRecipient::normalizeForGraph('+923004522663'));
        $this->assertSame('15557654321', WhatsAppRecipient::normalizeForGraph('+1 (555) 765-4321'));
    }
}
