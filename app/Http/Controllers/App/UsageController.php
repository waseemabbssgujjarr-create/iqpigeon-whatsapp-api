<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ApiRequest;
use App\Models\Message;
use App\Models\UsageRecord;
use App\Models\WebhookDelivery;
use App\Support\PartnerResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $partner = PartnerResolver::fromUser($request->user());

        $from = $request->date('from') ?? now()->subDays(30)->startOfDay();
        $to = $request->date('to') ?? now()->endOfDay();

        $usage = null;

        if ($partner !== null) {
            $usage = [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'api_requests' => ApiRequest::query()
                    ->where('partner_id', $partner->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->count(),
                'api_requests_failed' => ApiRequest::query()
                    ->where('partner_id', $partner->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->where('status_code', '>=', 400)
                    ->count(),
                'messages' => Message::query()
                    ->where('partner_id', $partner->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->count(),
                'messages_failed' => Message::query()
                    ->where('partner_id', $partner->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->where('status', \App\Enums\MessageStatus::Failed)
                    ->count(),
                'webhook_deliveries' => WebhookDelivery::query()
                    ->where('partner_id', $partner->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->count(),
                'webhook_deliveries_failed' => WebhookDelivery::query()
                    ->where('partner_id', $partner->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->where('status', \App\Enums\WebhookDeliveryStatus::Failed)
                    ->count(),
                'usage_records' => UsageRecord::query()
                    ->where('partner_id', $partner->id)
                    ->where('period_end', '>=', $from)
                    ->orderBy('metric')
                    ->get(['metric', 'quantity', 'period_start', 'period_end'])
                    ->map(fn ($r) => [
                        'metric' => $r->metric,
                        'quantity' => $r->quantity,
                        'period_start' => $r->period_start->toDateString(),
                        'period_end' => $r->period_end->toDateString(),
                    ]),
            ];
        }

        return Inertia::render('App/Usage', [
            'usage' => $usage,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }
}
