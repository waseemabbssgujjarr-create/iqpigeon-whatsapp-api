<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\WhatsappConnection;
use App\Services\Messaging\MessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestWhatsappSendCommand extends Command
{
    protected $signature = 'whatsapp:test-send
        {uuid : Connection UUID}
        {recipient : E.164 recipient}
        {template : Meta template element name}
        {--language=en_US : Template language code}
        {--confirm : Required to perform a live send}';

    protected $description = 'Live template send test (explicit --confirm). Never prints tokens.';

    public function handle(MessageService $messages): int
    {
        if (! $this->option('confirm')) {
            $this->error('Refusing to send without --confirm.');

            return self::FAILURE;
        }

        $connection = WhatsappConnection::query()
            ->where('uuid', $this->argument('uuid'))
            ->with('partner')
            ->first();

        if ($connection === null || $connection->partner === null) {
            $this->error('Connection or partner not found.');

            return self::FAILURE;
        }

        $templateName = strtolower(trim((string) $this->argument('template')));

        if (! preg_match('/^[a-z0-9_]+$/', $templateName)) {
            $this->error('Template name must be the Meta element name (lowercase, digits, underscores).');

            return self::FAILURE;
        }

        $message = $messages->queueOutbound(
            $connection->partner,
            $connection,
            (string) $this->argument('recipient'),
            'template',
            null,
            [
                'name' => $templateName,
                'language' => ['code' => (string) $this->option('language')],
            ],
        );

        $this->line('iqpigeon_message_uuid: '.$message->uuid);
        $this->line('initial_status: '.$message->status->value);
        $this->line('Poll with: php artisan message:inspect '.$message->uuid);

        return self::SUCCESS;
    }
}
