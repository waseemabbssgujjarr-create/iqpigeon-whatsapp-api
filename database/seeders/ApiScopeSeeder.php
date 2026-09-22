<?php

namespace Database\Seeders;

use App\Models\ApiScope;
use Illuminate\Database\Seeder;

class ApiScopeSeeder extends Seeder
{
    /**
     * @return array<string, array{description: string, group: string}>
     */
    public static function catalog(): array
    {
        return [
            'messages.send' => [
                'description' => 'Send WhatsApp messages',
                'group' => 'messages',
            ],
            'messages.read' => [
                'description' => 'Read message history and delivery status',
                'group' => 'messages',
            ],
            'connections.read' => [
                'description' => 'List WhatsApp connections and onboarding status',
                'group' => 'connections',
            ],
            'connections.write' => [
                'description' => 'Start onboarding sessions and disconnect numbers',
                'group' => 'connections',
            ],
            'templates.read' => [
                'description' => 'List WhatsApp message templates',
                'group' => 'templates',
            ],
            'templates.write' => [
                'description' => 'Manage template-related API operations',
                'group' => 'templates',
            ],
            'media.read' => [
                'description' => 'Read media metadata',
                'group' => 'media',
            ],
            'media.write' => [
                'description' => 'Upload and send media',
                'group' => 'media',
            ],
            'webhooks.read' => [
                'description' => 'List webhook endpoints and delivery history',
                'group' => 'webhooks',
            ],
            'webhooks.write' => [
                'description' => 'Create, update, and delete webhook endpoints',
                'group' => 'webhooks',
            ],
            'usage.read' => [
                'description' => 'Read IQPigeon infrastructure usage metrics',
                'group' => 'usage',
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::catalog() as $name => $meta) {
            ApiScope::query()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => $meta['description'],
                    'group' => $meta['group'],
                ],
            );
        }
    }
}
