<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\UsageRecord;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class UsageController extends Controller
{
    public function __invoke(Request $request): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        $records = UsageRecord::query()
            ->where('partner_id', $partner->id)
            ->where('period_end', '>=', now()->startOfMonth())
            ->orderBy('metric')
            ->get()
            ->map(fn (UsageRecord $record) => [
                'metric' => $record->metric,
                'quantity' => $record->quantity,
                'period_start' => $record->period_start->toIso8601String(),
                'period_end' => $record->period_end->toIso8601String(),
            ]);

        return ApiResponse::ok([
            'usage' => $records,
        ]);
    }
}
