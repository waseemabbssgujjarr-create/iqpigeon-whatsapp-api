<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMediaRequest;
use App\Http\Resources\MessageResource;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Services\Messaging\MessageService;
use App\Support\ApiResponse;

class MediaController extends Controller
{
    public function __construct(
        private readonly MessageService $messages,
    ) {}

    public function index(): ApiResponse
    {
        return ApiResponse::ok(['media' => []]);
    }

    public function store(StoreMediaRequest $request): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        $connection = WhatsappConnection::query()
            ->where('partner_id', $partner->id)
            ->where('uuid', $request->validated('connection_id'))
            ->first();

        if ($connection === null) {
            return ApiResponse::error('connection_not_found', 'Connection not found.', 404);
        }

        $type = $request->validated('type');
        $mediaPayload = $request->validated('media');

        if ($request->filled('caption')) {
            $mediaPayload['caption'] = $request->validated('caption');
        }

        $message = $this->messages->queueOutbound(
            $partner,
            $connection,
            $request->validated('to'),
            $type,
            null,
            $mediaPayload,
        );

        return ApiResponse::ok([
            'message' => new MessageResource($message->load('whatsappConnection')),
        ], 202);
    }
}
