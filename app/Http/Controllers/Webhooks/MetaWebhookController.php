<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Meta\MetaWebhookProcessor;
use App\Services\Meta\MetaWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MetaWebhookController extends Controller
{
    public function __construct(
        private readonly MetaWebhookVerifier $verifier,
        private readonly MetaWebhookProcessor $processor,
    ) {}

    public function __invoke(Request $request): Response|string
    {
        if ($request->isMethod('GET')) {
            $challenge = $this->verifier->subscriptionChallenge($request);

            if ($challenge === null) {
                return response('Forbidden', 403);
            }

            return response($challenge, 200);
        }

        if (! $this->verifier->verifySignature($request)) {
            return response('Invalid signature', 403);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();
        $this->processor->persistAndDispatch($payload);

        return response('EVENT_RECEIVED', 200);
    }
}
