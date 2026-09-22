<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ApiScope;
use App\Services\ApiKeyService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    public function index(Request $request): Response
    {
        $partner = $request->user()?->partner ?? $request->user()?->ownedPartner;

        $keys = $partner
            ? $partner->apiKeys()->with('scopes')->orderByDesc('id')->get()
            : collect();

        $scopes = ApiScope::query()->orderBy('name')->get(['id', 'name', 'description']);

        return Inertia::render('App/ApiKeys', [
            'apiKeys' => $keys->map(fn ($key) => [
                'uuid' => $key->uuid,
                'label' => $key->label,
                'prefix' => $key->prefix,
                'scopes' => $key->scopes->pluck('name'),
                'revoked_at' => $key->revoked_at?->toIso8601String(),
                'expires_at' => $key->expires_at?->toIso8601String(),
                'last_used_at' => $key->last_used_at?->toIso8601String(),
            ]),
            'scopes' => $scopes,
            'flashSecret' => $request->session()->pull('api_key_secret'),
        ]);
    }

    public function store(Request $request, ApiKeyService $apiKeys, AuditLogService $audit): RedirectResponse
    {
        $partner = $request->user()?->partner ?? $request->user()?->ownedPartner;
        abort_if($partner === null, 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', 'exists:api_scopes,name'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $generated = $apiKeys->generate($partner, $validated['label'], $validated['scopes']);

        if (! empty($validated['expires_at'])) {
            $generated['api_key']->forceFill([
                'expires_at' => $validated['expires_at'],
            ])->save();
        }

        $audit->log('api_key.created', $partner, $request->user(), $generated['api_key'], ip: $request->ip());

        return redirect()
            ->route('app.api-keys')
            ->with('api_key_secret', $generated['secret']);
    }

    public function destroy(Request $request, string $uuid, ApiKeyService $apiKeys, AuditLogService $audit): RedirectResponse
    {
        $partner = $request->user()?->partner ?? $request->user()?->ownedPartner;
        abort_if($partner === null, 403);

        $key = $partner->apiKeys()->where('uuid', $uuid)->firstOrFail();
        $apiKeys->revoke($key);
        $audit->log('api_key.revoked', $partner, $request->user(), $key, ip: $request->ip());

        return redirect()->route('app.api-keys');
    }
}
