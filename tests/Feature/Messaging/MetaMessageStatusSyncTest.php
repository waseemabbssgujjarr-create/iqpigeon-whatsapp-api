<?php

namespace Tests\Feature\Messaging;

use App\Enums\ConnectionStatus;
use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Services\Messaging\MetaMessageStatusSync;
use Illuminate\Support\Str;
use Tests\TestCase;

class MetaMessageStatusSyncTest extends TestCase
{
    public function test_meta_failed_status_updates_message_with_safe_diagnostics(): void
    {
        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => '1001',
            'connection_status' => ConnectionStatus::Active,
        ]);

        $message = Message::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'direction' => 'outbound',
            'status' => MessageStatus::Sent,
            'wa_message_id' => 'wamid.abc123',
            'to_number' => '923004522663',
            'message_type' => 'text',
            'body' => 'test',
        ]);

        app(MetaMessageStatusSync::class)->applyFromWebhookValue([
            'statuses' => [
                [
                    'id' => 'wamid.abc123',
                    'status' => 'failed',
                    'recipient_id' => '923004522663',
                    'errors' => [
                        [
                            'code' => 131026,
                            'title' => 'Message undeliverable',
                            'message' => 'Receiver is not a valid WhatsApp user',
                        ],
                    ],
                ],
            ],
        ]);

        $message->refresh();
        $this->assertSame(MessageStatus::Failed, $message->status);
        $this->assertSame('meta_status_131026', $message->failure_code);
        $this->assertStringContainsString('131026', (string) $message->failure_message);
    }

    public function test_meta_delivered_status_sets_delivered_at(): void
    {
        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => '1001',
            'connection_status' => ConnectionStatus::Active,
        ]);

        $message = Message::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'direction' => 'outbound',
            'status' => MessageStatus::Sent,
            'wa_message_id' => 'wamid.delivered1',
            'to_number' => '923004522663',
            'message_type' => 'text',
        ]);

        app(MetaMessageStatusSync::class)->applyFromWebhookValue([
            'statuses' => [
                [
                    'id' => 'wamid.delivered1',
                    'status' => 'delivered',
                    'timestamp' => (string) now()->timestamp,
                ],
            ],
        ]);

        $message->refresh();
        $this->assertSame(MessageStatus::Delivered, $message->status);
        $this->assertNotNull($message->delivered_at);
    }
}
