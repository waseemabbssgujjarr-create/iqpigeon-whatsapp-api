<?php

namespace App\Http\Controllers\Debug;

use App\Http\Controllers\Controller;
use App\Support\MetaEmbeddedSignupDiagnosticReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Temporary read-only Meta Embedded Signup diagnostics — remove after debugging.
 */
class MetaEmbeddedSignupDiagnosticController extends Controller
{
    public function __invoke(Request $request, MetaEmbeddedSignupDiagnosticReport $report): View
    {
        $data = $report->build($request->user());

        return view('debug.meta-embedded-signup', [
            'report' => $data,
            'appUrl' => config('app.url'),
        ]);
    }
}
