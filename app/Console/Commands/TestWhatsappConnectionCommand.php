<?php

namespace App\Console\Commands;

use App\Models\WhatsappConnection;
use App\Services\Meta\WhatsappCloudApiRegistrationService;
use App\Services\Meta\WhatsappConnectionValidator;
use Illuminate\Console\Command;

class TestWhatsappConnectionCommand extends Command
{
    protected $signature = 'whatsapp:test-connection {uuid : Connection UUID}';

    protected $description = 'Read-only connection health checks (no message send).';

    public function handle(
        WhatsappConnectionValidator $validator,
        WhatsappCloudApiRegistrationService $registration,
    ): int {
        $connection = WhatsappConnection::query()
            ->where('uuid', $this->argument('uuid'))
            ->with('credentials')
            ->first();

        if ($connection === null) {
            $this->error('Connection not found.');

            return self::FAILURE;
        }

        $this->line('connection_uuid: '.$connection->uuid);
        $this->line('connection_status: '.$connection->connection_status->value);
        $this->line('waba_id: '.($connection->waba_id ?? '(null)'));
        $this->line('phone_number_id: '.($connection->phone_number_id ?? '(null)'));
        $this->line('display_phone_number: '.($connection->display_phone_number ?? '(null)'));
        $this->line('credential_exists: '.($connection->credentials !== null ? 'yes' : 'no'));
        $this->line('cloud_api_registered: '.($registration->isRegisteredForSending($connection) ? 'yes' : 'no'));

        $metadata = is_array($connection->metadata) ? $connection->metadata : [];
        $this->line('onboarding_source: '.(string) ($metadata['onboarding_source'] ?? '(unknown)'));

        $result = $validator->validate($connection);
        foreach ($result['checks'] as $check) {
            $status = ($check['ok'] ?? false) ? 'OK' : 'FAIL';
            $detail = $check['detail'] ?? '';
            $this->line(sprintf('check[%s]: %s%s', $check['key'], $status, $detail !== '' ? ' — '.$detail : ''));
        }

        return ($result['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
