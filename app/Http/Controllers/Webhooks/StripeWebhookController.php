<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Stripe\StripeWebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripeWebhookProcessor $processor,
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $this->processor->process(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (SignatureVerificationException) {
            return response('Invalid signature', 400);
        } catch (\RuntimeException $exception) {
            return response($exception->getMessage(), 500);
        }

        return response('OK', 200);
    }
}
