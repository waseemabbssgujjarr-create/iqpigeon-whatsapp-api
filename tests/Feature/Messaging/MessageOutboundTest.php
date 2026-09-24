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
        $this->assertNull($message->failure_code);
    }

    public function test_outbound_job_strips_plus_from_recipient_for_graph(): void
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
            'to_number' => '+923004522663',
            'message_type' => 'text',
            'body' => 'API testing',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.e164fix']]], 200),
        ]);

        (new SendOutboundMessageJob($message->id))->handle(app(\App\Services\Meta\MetaClient::class));

        Http::assertSent(function ($request) {
            $body = $request->data();

            return ($body['to'] ?? null) === '923004522663'
                && ($body['messaging_product'] ?? null) === 'whatsapp'
                && ($body['type'] ?? null) === 'text'
                && ($body['text']['body'] ?? null) === 'API testing';
        });

        $this->assertSame(MessageStatus::Sent, $message->fresh()->status);
    }

    public function test_outbound_job_uses_connection_phone_number_id_not_uuid(): void
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
            'body' => 'Path check',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.path']]], 200),
        ]);

        (new SendOutboundMessageJob($message->id))->handle(app(\App\Services\Meta\MetaClient::class));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/phone_999/messages');
        });
    }

    public function test_outbound_job_marks_message_failed_on_graph_failure_with_safe_detail(): void
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
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid parameter',
                    'type' => 'OAuthException',
                    'code' => 100,
                ],
            ], 400),
        ]);

        (new SendOutboundMessageJob($message->id))->handle(app(\App\Services\Meta\MetaClient::class));

        $message->refresh();
        $this->assertSame(MessageStatus::Failed, $message->status);
        $this->assertSame('graph_request_failed', $message->failure_code);
        $this->assertStringContainsString('HTTP 400', (string) $message->failure_message);
        $this->assertStringContainsString('Meta error 100', (string) $message->failure_message);
        $this->assertNull($message->wa_message_id);
    }

    public function test_outbound_job_fails_when_phone_number_id_missing(): void
    {
        ['partner' => $partner] = $this->createActivePartnerWithApiKey(['messages.send']);
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'external_ref' => 'no-phone',
            'phone_number_id' => null,
            'display_phone_number' => null,
            'connection_status' => ConnectionStatus::Active,
            'connected_at' => now(),
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $connection->id,
            'access_token' => 'encrypted-test-token',
            'phone_number_id' => null,
        ]);

        $message = Message::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'direction' => 'outbound',
            'status' => MessageStatus::Queued,
            'to_number' => '15557654321',
            'message_type' => 'text',
            'body' => 'No phone id',
        ]);

        Http::fake();

        (new SendOutboundMessageJob($message->id))->handle(app(\App\Services\Meta\MetaClient::class));

        Http::assertNothingSent();

        $message->refresh();
        $this->assertSame(MessageStatus::Failed, $message->status);
        $this->assertSame('missing_phone_number_id', $message->failure_code);
    }

    public function test_post_message_rejects_fake_active_connection_without_phone_number_id(): void
    {
        ['partner' => $partner, 'secret' => $secret] = $this->createActivePartnerWithApiKey(['messages.send']);
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'external_ref' => 'fake-active',
            'phone_number_id' => null,
            'connection_status' => ConnectionStatus::Active,
            'connected_at' => now(),
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $connection->id,
            'access_token' => 'encrypted-test-token',
        ]);

        $this->postJson('/api/v1/messages', [
            'connection_id' => $connection->uuid,
            'to' => '15557654321',
            'type' => 'text',
            'body' => 'Should not queue',
        ], array_merge($this->withBearer($secret), [
            'Idempotency-Key' => 'msg-fake-'.uniqid(),
        ]))->assertStatus(422);

        $this->assertSame(0, Message::query()->count());
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
