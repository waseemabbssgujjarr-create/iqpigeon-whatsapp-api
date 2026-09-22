<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartnerResource;
use App\Models\Partner;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        return ApiResponse::ok([
            'partner' => new PartnerResource($partner),
        ]);
    }
}
