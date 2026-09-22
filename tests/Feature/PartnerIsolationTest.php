<?php

namespace Tests\Feature;

use App\Models\Message;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class PartnerIsolationTest extends TestCase
{
    use CreatesApiPartners;

    public function test_partner_cannot_read_another_partners_message(): void
    {
        ['partner' => $partnerA, 'secret' => $secretA] = $this->createActivePartnerWithApiKey(['messages.read']);
        ['partner' => $partnerB] = $this->createActivePartnerWithApiKey(['messages.read']);

        $connectionB = $partnerB->whatsappConnections()->create([
            'uuid' => (string) Str::uuid(),
            'connection_status' => 'pending',
        ]);

        $messageB = Message::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partnerB->id,
            'whatsapp_connection_id' => $connectionB->id,
            'direction' => 'outbound',
            'status' => 'queued',
            'to_number' => '+15551234567',
            'message_type' => 'text',
            'body' => 'secret',
        ]);

        $response = $this->getJson('/api/v1/messages/'.$messageB->uuid, $this->withBearer($secretA));

        $response->assertNotFound()
            ->assertJsonPath('error.code', 'message_not_found');

        $this->assertSame($partnerA->id, $partnerA->fresh()->id);
    }
}
