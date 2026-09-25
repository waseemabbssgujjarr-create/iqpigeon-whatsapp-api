<?php

namespace App\Services\Meta;

use App\Models\WhatsappConnection;
use RuntimeException;

class WhatsappMessageTemplateLister
{
    public function __construct(
        private readonly MetaClient $metaClient,
    ) {}

    /**
     * @return list<array{name: string, language: string, status: string|null, category: string|null, id: string|null}>
     */
    public function listForConnection(WhatsappConnection $connection): array
    {
        $wabaId = trim((string) ($connection->waba_id ?? ''));
        if ($wabaId === '') {
            throw new RuntimeException('Connection has no WABA id.');
        }

        $connection->loadMissing('credentials');
        $credentials = $connection->credentials;

        if ($credentials === null || trim((string) $credentials->access_token) === '') {
            throw new RuntimeException('Connection has no stored Meta access token.');
        }

        return $this->listForWaba($wabaId, (string) $credentials->access_token);
    }

    /**
     * @return list<array{name: string, language: string, status: string|null, category: string|null, id: string|null}>
     */
    public function listForWaba(string $wabaId, string $accessToken): array
    {
        $wabaId = trim($wabaId);
        if ($wabaId === '') {
            throw new RuntimeException('WABA id is required.');
        }

        $templates = [];
        $query = ['limit' => 100];

        do {
            $response = $this->metaClient->graph(
                'GET',
                $wabaId.'/message_templates',
                $query,
                [],
                $accessToken,
            );

            if ($response->failed()) {
                $providerCode = (string) data_get($response->json(), 'error.code', '');
                $providerMessage = trim((string) data_get($response->json(), 'error.message', 'Meta Graph request failed.'));

                throw new RuntimeException(
                    'Meta message_templates request failed (HTTP '.$response->status()
                    .($providerCode !== '' ? ', Meta error '.$providerCode : '')
                    .'): '.$providerMessage,
                );
            }

            $json = $response->json();
            $rows = data_get($json, 'data', []);

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (is_array($row)) {
                        $templates[] = $this->normalizeRow($row);
                    }
                }
            }

            $after = data_get($json, 'paging.cursors.after');
            if (! is_string($after) || $after === '') {
                break;
            }

            $query['after'] = $after;
        } while (true);

        return $templates;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{name: string, language: string, status: string|null, category: string|null, id: string|null}
     */
    private function normalizeRow(array $row): array
    {
        $language = $row['language'] ?? '';
        if (is_array($language)) {
            $language = $language['code'] ?? '';
        }

        return [
            'name' => trim((string) ($row['name'] ?? '')),
            'language' => trim((string) $language),
            'status' => isset($row['status']) ? trim((string) $row['status']) : null,
            'category' => isset($row['category']) ? trim((string) $row['category']) : null,
            'id' => isset($row['id']) ? trim((string) $row['id']) : null,
        ];
    }
}
