<?php

namespace Tests\Unit\Services\Meta;

use App\Services\Meta\MetaClient;
use App\Services\Meta\WhatsappMessageTemplateLister;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappMessageTemplateListerTest extends TestCase
{
    public function test_lists_templates_with_pagination_and_normalizes_rows(): void
    {
        config([
            'services.meta.app_id' => 'app',
            'services.meta.app_secret' => 'secret',
            'services.meta.graph_version' => 'v21.0',
        ]);

        Http::fake([
            'https://graph.facebook.com/v21.0/waba_1/message_templates*' => Http::sequence()
                ->push([
                    'data' => [
                        [
                            'id' => '111',
                            'name' => 'order_update',
                            'language' => 'en_US',
                            'status' => 'APPROVED',
                            'category' => 'UTILITY',
                        ],
                    ],
                    'paging' => ['cursors' => ['after' => 'cursor_2']],
                ])
                ->push([
                    'data' => [
                        [
                            'id' => '222',
                            'name' => 'welcome',
                            'language' => ['code' => 'en'],
                            'status' => 'PENDING',
                            'category' => 'MARKETING',
                        ],
                    ],
                    'paging' => ['cursors' => ['after' => null]],
                ]),
        ]);

        $lister = new WhatsappMessageTemplateLister(app(MetaClient::class));
        $rows = $lister->listForWaba('waba_1', 'user-access-token');

        $this->assertCount(2, $rows);
        $this->assertSame([
            'name' => 'order_update',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'category' => 'UTILITY',
            'id' => '111',
        ], $rows[0]);
        $this->assertSame([
            'name' => 'welcome',
            'language' => 'en',
            'status' => 'PENDING',
            'category' => 'MARKETING',
            'id' => '222',
        ], $rows[1]);

        Http::assertSentCount(2);
    }

    public function test_meta_failure_does_not_leak_access_token(): void
    {
        config([
            'services.meta.app_id' => 'app',
            'services.meta.app_secret' => 'secret',
            'services.meta.graph_version' => 'v21.0',
        ]);

        Http::fake([
            'https://graph.facebook.com/v21.0/waba_1/message_templates*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token.',
                    'code' => 190,
                ],
            ], 401),
        ]);

        $lister = new WhatsappMessageTemplateLister(app(MetaClient::class));

        try {
            $lister->listForWaba('waba_1', 'secret-user-token-should-not-appear');
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('HTTP 401', $exception->getMessage());
            $this->assertStringContainsString('Meta error 190', $exception->getMessage());
            $this->assertStringNotContainsString('secret-user-token', $exception->getMessage());
        }
    }
}
