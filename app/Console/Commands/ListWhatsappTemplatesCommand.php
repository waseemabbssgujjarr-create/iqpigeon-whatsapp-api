<?php

namespace App\Console\Commands;

use App\Models\WhatsappConnection;
use App\Services\Meta\WhatsappMessageTemplateLister;
use Illuminate\Console\Command;
use Throwable;

class ListWhatsappTemplatesCommand extends Command
{
    protected $signature = 'whatsapp:list-templates {uuid : Connection UUID}';

    protected $description = 'List Meta message templates for a connection WABA (read-only, no token output).';

    public function handle(WhatsappMessageTemplateLister $lister): int
    {
        $connection = WhatsappConnection::query()
            ->where('uuid', $this->argument('uuid'))
            ->with('credentials')
            ->first();

        if ($connection === null) {
            $this->error('Connection not found.');

            return self::FAILURE;
        }

        $this->line('connection_uuid: '.$connection->uuid);
        $this->line('waba_id: '.($connection->waba_id ?? '(null)'));
        $this->line('phone_number_id: '.($connection->phone_number_id ?? '(null)'));
        $this->line('display_phone_number: '.($connection->display_phone_number ?? '(null)'));
        $this->newLine();

        try {
            $templates = $lister->listForConnection($connection);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($templates === []) {
            $this->warn('No templates returned for this WABA.');

            return self::SUCCESS;
        }

        $this->table(
            ['name', 'language', 'status', 'category', 'id'],
            array_map(static fn (array $row): array => [
                $row['name'],
                $row['language'],
                $row['status'] ?? '—',
                $row['category'] ?? '—',
                $row['id'] ?? '—',
            ], $templates),
        );

        $this->newLine();
        $this->line('Total templates: '.count($templates));

        return self::SUCCESS;
    }
}
