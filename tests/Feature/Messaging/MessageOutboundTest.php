<?php

namespace Tests\Feature\Messaging;

use App\Enums\ConnectionStatus;
use App\Enums\MessageStatus;
use App\Jobs\SendOutboundMessageJob;
use App\Models\Message;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionCredential;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class MessageOutboundTest extends TestCase
{
    use CreatesApiPartners;

    public function test_post_message_dispatches_outbound_job(): void
    {
        Bus::fake([SendOutboundMessageJob::class]);

        ['partner' => $partner, 'secret' => $secret] = $this->createActivePartnerWithApiKey(['messages.send']);
        $connection = $this->createActiveWhatsappConnection($partner);

        $headers = array_merge($this->withBearer($secret), [
            'Idempotency-Key' => 'msg-'.uniqid(),
        ]);

        $this->postJson('/api/v1/messages', [
            'connection_id' => $connection->uuid,
            'to' => '15557654321',
            'type' => 'text',
            'body' => 'Queued message',
        ], $headers)->assertAccepted();

        Bus::assertDispatched(SendOutboundMessageJob::class);
    }

    public function test_outbound_job_marks_message_sent_on_graph_success(): void
    {
        ['partner' => $partner] = $this->createActivePartnerWithApiKey(['messages.send']);
        $connection = $this->createActiveWhatsappConnection($partner);

        $message = Message::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'direction' => 'outbound',
            'status' => MessageStatus::Queued,
            'from_number' => $connection->display_phone_number,
            'to_number' => '15557654321',
            'message_type' => 'text',
            'body' => 'Direct job test',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test123']]], 200),
        ]);

        (new SendOutboundMessageJob($message->id))->handle(app(\App\Services\Meta\MetaClient::class));

        $message->refresh();
        $this->assertSame(MessageStatus::Sent, $message->status);
        $this->assertSame('wamid.test123', $message->wa_message_id);
    }

    public function test_outbound_job_marks_message_failed_on_graph_failure(): void
    {
        ['partner' => $partner] = $this->createActivePartnerWithApiKey(['messages.send']);
        $connection = $this->createActiveWhatsappConnection($partner);

        $message = Message::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'direction' => 'outbound',
            'status' => MessageStatus::Queued,
            'from_number' => $connection->display_phone_number,
            'to_number' => '15557654321',
            'message_type' => 'text',
            'body' => 'Failure path',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'fail']], 400),
        ]);

        (new SendOutboundMessageJob($message->id))->handle(app(\App\Services\Meta\MetaClient::class));

        $this->assertSame(MessageStatus::Failed, $message->fresh()->status);
    }

    private function createActiveWhatsappConnection(Partner $partner): WhatsappConnection
    {
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'external_ref' => 'msg-test',
            'phone_number_id' => 'phone_999',
            'display_phone_number' => '+15559998888',
            'connection_status' => ConnectionStatus::Active,
            'connected_at' => now(),
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $connection->id,
            'access_token' => 'encrypted-test-token',
            'phone_number_id' => 'phone_999',
        ]);

        return $connection;
    }
}
