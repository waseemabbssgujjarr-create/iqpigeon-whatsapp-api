<?php

namespace App\Console\Commands;

use App\Models\WhatsappConnection;
use App\Services\Meta\WhatsappConnectionHydrator;
use Illuminate\Console\Command;

class RehydrateWhatsappConnectionCommand extends Command
{
    protected $signature = 'whatsapp:rehydrate-connection
                            {uuid? : Connection UUID to rehydrate from Meta}
                            {--reconcile-stale : Demote Active connections missing phone_number_id}
                            {--all : Rehydrate every connection that has stored credentials}';

    protected $description = 'Re-fetch WhatsApp phone number IDs from Meta (no secrets printed).';

    public function handle(WhatsappConnectionHydrator $hydrator): int
    {
        $uuid = $this->argument('uuid');

        if ($this->option('reconcile-stale')) {
            $demoted = 0;
            WhatsappConnection::query()->with('credentials')->chunkById(100, function ($connections) use ($hydrator, &$demoted): void {
                foreach ($connections as $connection) {
                    if ($hydrator->reconcileOperationalStatus($connection)) {
                        $demoted++;
                        $this->line('Demoted incomplete active connection '.$connection->uuid);
                    }
                }
            });

            $this->info('Reconciled stale active connections: '.$demoted.' demoted.');

            if ($uuid === null && ! $this->option('all')) {
                return self::SUCCESS;
            }
        }

        if ($uuid !== null) {
            return $this->rehydrateOne($hydrator, (string) $uuid);
        }

        if ($this->option('all')) {
            $ok = 0;
            $failed = 0;

            WhatsappConnection::query()->with('credentials')->chunkById(100, function ($connections) use ($hydrator, &$ok, &$failed): void {
                foreach ($connections as $connection) {
                    if ($connection->credentials === null) {
                        continue;
                    }

                    if ($hydrator->rehydrateFromStoredCredentials($connection)) {
                        $hydrator->applyOperationalStatusAfterHydration($connection->fresh());
                        $ok++;
                        $this->line('Hydrated '.$connection->uuid);
                    } else {
                        $failed++;
                        $this->warn('Hydration failed '.$connection->uuid);
                    }
                }
            });

            $this->info("Rehydration finished: {$ok} succeeded, {$failed} failed.");

            return self::SUCCESS;
        }

        $this->error('Provide a connection UUID, --all, or --reconcile-stale.');

        return self::FAILURE;
    }

    private function rehydrateOne(WhatsappConnectionHydrator $hydrator, string $uuid): int
    {
        $connection = WhatsappConnection::query()->where('uuid', $uuid)->with('credentials')->first();

        if ($connection === null) {
            $this->error('Connection not found.');

            return self::FAILURE;
        }

        $this->line('Connection UUID: '.$connection->uuid);
        $this->line('Status: '.$connection->connection_status->value);
        $this->line('phone_number_id: '.($connection->phone_number_id ?? '(null)'));
        $this->line('display_phone_number: '.($connection->display_phone_number ?? '(null)'));
        $this->line('waba_id: '.($connection->waba_id ?? '(null)'));
        $this->line('Has credentials: '.($connection->credentials !== null ? 'yes' : 'no'));

        if ($connection->credentials === null) {
            $this->error('No stored Meta credentials — reconnect via Embedded Signup.');

            return self::FAILURE;
        }

        if ($hydrator->rehydrateFromStoredCredentials($connection)) {
            $hydrator->applyOperationalStatusAfterHydration($connection->fresh());
            $connection->refresh();
            $this->info('Hydration succeeded.');
            $this->line('phone_number_id: '.($connection->phone_number_id ?? '(null)'));
            $this->line('Status: '.$connection->connection_status->value);

            return self::SUCCESS;
        }

        $hydrator->reconcileOperationalStatus($connection->fresh());
        $this->error('Meta hydration failed — token may be invalid or WABA has no phone numbers.');

        return self::FAILURE;
    }
}
