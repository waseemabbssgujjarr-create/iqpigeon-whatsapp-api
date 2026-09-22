<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Services\Messaging\MessageService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private readonly MessageService $messages,
    ) {}

    public function index(Request $request): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        $messages = Message::query()
            ->where('partner_id', $partner->id)
            ->with('whatsappConnection')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return ApiResponse::ok([
            'messages' => MessageResource::collection($messages),
        ]);
    }

    public function store(StoreMessageRequest $request): ApiResponse
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
        $payload = $request->validated('template') ?? $request->validated('payload') ?? [];

        $message = $this->messages->queueOutbound(
            $partner,
            $connection,
            $request->validated('to'),
            $type,
            $request->validated('body'),
            is_array($payload) ? $payload : [],
        );

        return ApiResponse::ok([
            'message' => new MessageResource($message->load('whatsappConnection')),
        ], 202);
    }

    public function show(Request $request, string $id): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        $message = Message::query()
            ->where('partner_id', $partner->id)
            ->where('uuid', $id)
            ->with('whatsappConnection')
            ->first();

        if ($message === null) {
            return ApiResponse::error('message_not_found', 'Message not found.', 404);
        }

        return ApiResponse::ok([
            'message' => new MessageResource($message),
        ]);
    }
}
