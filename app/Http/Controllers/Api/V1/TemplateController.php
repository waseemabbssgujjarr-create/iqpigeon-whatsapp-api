<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Services\Meta\MetaClient;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function __construct(
        private readonly MetaClient $metaClient,
    ) {}

    public function index(Request $request): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        $connection = WhatsappConnection::query()
            ->where('partner_id', $partner->id)
            ->whereNotNull('waba_id')
            ->orderByDesc('id')
            ->first();

        if ($connection === null || $connection->waba_id === null) {
            return ApiResponse::ok(['templates' => []]);
        }

        $credentials = $connection->credentials;

        if ($credentials === null) {
            return ApiResponse::ok(['templates' => []]);
        }

        $response = $this->metaClient->graph(
            'GET',
            $connection->waba_id.'/message_templates',
            accessToken: $credentials->access_token,
        );

        if ($response->failed()) {
            return ApiResponse::error('templates_unavailable', 'Unable to fetch templates from Meta.', 502);
        }

        return ApiResponse::ok([
            'templates' => data_get($response->json(), 'data', []),
        ]);
    }

    public function sync(): ApiResponse
    {
        return ApiResponse::ok(['synced' => true]);
    }
}
