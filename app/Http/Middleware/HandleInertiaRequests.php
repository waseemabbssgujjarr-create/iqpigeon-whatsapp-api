<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'email_verified' => $request->user()->hasVerifiedEmail(),
                ] : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'verified' => fn () => $request->query('verified') === '1',
                'coexistence_onboarding' => fn () => $request->session()->get('coexistence_onboarding'),
            ],
            'metaEmbeddedSignup' => fn () => [
                'app_id' => (string) config('services.meta.app_id'),
                'graph_version' => ltrim((string) config('services.meta.graph_version', 'v21.0'), '/'),
                'config_id_standard' => (string) config('services.meta.es_config_id'),
                'config_id_coexistence' => (string) (config('services.meta.es_config_id_coexistence') ?: config('services.meta.es_config_id')),
            ],
            'socialAuth' => fn () => [
                'google' => (string) config('services.google.client_id') !== ''
                    && (string) config('services.google.client_secret') !== '',
                'facebook' => (string) config('services.facebook.client_id') !== ''
                    && (string) config('services.facebook.client_secret') !== '',
            ],
        ];
    }
}
