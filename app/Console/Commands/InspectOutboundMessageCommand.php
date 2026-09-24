<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\WhatsappConnection;
use Illuminate\Console\Command;

class InspectOutboundMessageCommand extends Command
{
    protected $signature = 'message:inspect {uuid : Message UUID}';

    protected $description = 'Print safe outbound message and connection fields (no secrets).';

    public function handle(): int
    {
        $message = Message::query()
            ->where('uuid', $this->argument('uuid'))
            ->with('whatsappConnection.credentials')
            ->first();

        if ($message === null) {
            $this->error('Message not found.');

            return self::FAILURE;
        }

        $connection = $message->whatsappConnection;

        $this->line('--- message ---');
        $this->line('uuid: '.$message->uuid);
        $this->line('direction: '.$message->direction);
        $this->line('status: '.($message->status?->value ?? ''));
        $this->line('to: '.($message->to_number ?? ''));
        $this->line('from: '.($message->from_number ?? ''));
        $this->line('type: '.$message->message_type);
        $this->line('body: '.substr((string) ($message->body ?? ''), 0, 200));
        $this->line('wa_message_id: '.($message->wa_message_id ?? '(null)'));
        $this->line('failure_code: '.($message->failure_code ?? '(null)'));
        $this->line('failure_message: '.($message->failure_message ?? '(null)'));
        $this->line('sent_at: '.($message->sent_at?->toIso8601String() ?? '(null)'));
        $this->line('delivered_at: '.($message->delivered_at?->toIso8601String() ?? '(null)'));
        $this->line('read_at: '.($message->read_at?->toIso8601String() ?? '(null)'));
        $this->line('created_at: '.$message->created_at?->toIso8601String());

        if ($connection instanceof WhatsappConnection) {
            $this->line('--- connection ---');
            $this->line('connection_uuid: '.$connection->uuid);
            $this->line('connection_status: '.$connection->connection_status->value);
            $this->line('phone_number_id: '.($connection->phone_number_id ?? '(null)'));
            $this->line('display_phone_number: '.($connection->display_phone_number ?? '(null)'));
            $this->line('waba_id: '.($connection->waba_id ?? '(null)'));
            $this->line('credential_exists: '.($connection->credentials !== null ? 'yes' : 'no'));
            $this->line('credential_phone_number_id: '.($connection->credentials?->phone_number_id ?? '(null)'));
        }

        return self::SUCCESS;
    }
}
