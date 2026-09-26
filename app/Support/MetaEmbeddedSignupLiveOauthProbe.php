<?php

namespace App\Support;

use App\Http\Controllers\OAuth\MetaOAuthController;
use App\Models\User;
use App\Services\ConnectionOnboardingService;
use App\Services\Meta\MetaEmbeddedSignupService;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/**
 * Compare direct authorizationUrl() vs actual /oauth/meta/start HTTP behavior (read-only vs Meta).
 */
final class MetaEmbeddedSignupLiveOauthProbe
{
    public function __construct(
        private readonly ConnectionOnboardingService $onboarding,
        private readonly MetaEmbeddedSignupService $embeddedSignup,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function run(?User $user): array
    {
        $runtime = $this->runtimePaths();
        $routeInfo = $this->routeInfo();

        $sessionProbe = $this->createDiagnosticSession($user);
        $comparison = [
            'session_probe' => $sessionProbe['meta'],
            'direct_controller_path' => null,
            'laravel_subrequest' => null,
            'external_http' => null,
            'comparison' => [],
            'conclusion' => ['code' => '?', 'message' => 'Insufficient data'],
        ];

        if (! $sessionProbe['ok']) {
            $comparison['conclusion'] = [
                'code' => '?',
                'message' => $sessionProbe['meta']['error'] ?? 'Could not create diagnostic session.',
            ];

            return [
                'runtime' => $runtime,
                'route' => $routeInfo,
                'probe' => $comparison,
                'generated_at' => now()->toIso8601String(),
            ];
        }

        $token = $sessionProbe['token'];
        $tokenFingerprint = substr(hash('sha256', $token), 0, 12);

        $comparison['direct_controller_path'] = $this->probeDirectControllerPath($token);
        $comparison['laravel_subrequest'] = $this->probeLaravelSubrequest($token);
        $comparison['external_http'] = $this->probeExternalHttp($token);

        $comparison['comparison'] = $this->compareProbes(
            $comparison['direct_controller_path'],
            $comparison['laravel_subrequest'],
            $comparison['external_http'],
        );
        $comparison['session_probe']['token_fingerprint_sha256_prefix'] = $tokenFingerprint;
        unset($comparison['session_probe']['note']);

        $comparison['conclusion'] = $this->conclude($comparison['comparison'], $runtime);

        return [
            'runtime' => $runtime,
            'route' => $routeInfo,
            'probe' => $comparison,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{ok: bool, token: string|null, meta: array<string, mixed>}
     */
    private function createDiagnosticSession(?User $user): array
    {
        if ($user === null) {
            return [
                'ok' => false,
                'token' => null,
                'meta' => ['error' => 'Authenticated user required.'],
            ];
        }

        $partner = PartnerResolver::fromUser($user);
        if ($partner === null) {
            return [
                'ok' => false,
                'token' => null,
                'meta' => ['error' => 'User has no partner — cannot create diagnostic onboarding session.'],
            ];
        }

        try {
            $result = $this->onboarding->startOnboarding($partner, onboardingSource: 'coexistence');
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'token' => null,
                'meta' => ['error' => 'startOnboarding failed: '.$e->getMessage()],
            ];
        }

        return [
            'ok' => true,
            'token' => $result['session_token'],
            'meta' => [
                'connection_uuid' => $result['connection']->uuid,
                'onboarding_source' => 'coexistence',
                'note' => 'Creates one pending whatsapp_connections row + embedded_signup_sessions row for this probe.',
                'oauth_start_path' => '/oauth/meta/start?token=[redacted]',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function probeDirectControllerPath(string $token): array
    {
        $session = $this->onboarding->findSessionByToken($token);
        if ($session === null) {
            return [
                'label' => 'A_direct_authorizationUrl',
                'status' => 'FAIL',
                'error' => 'findSessionByToken returned null',
            ];
        }

        $location = $this->embeddedSignup->authorizationUrl($session, $token);
        $parsed = $this->parseFacebookOAuthLocation($location);

        return array_merge($parsed, [
            'label' => 'A_direct_authorizationUrl',
            'controller' => MetaOAuthController::class.'::start (logic)',
            'service' => MetaEmbeddedSignupService::class.'::authorizationUrl',
            'status' => 'OK',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function probeLaravelSubrequest(string $token): array
    {
        $path = '/oauth/meta/start?token='.urlencode($token);
        $request = Request::create($path, 'GET');
        $request->headers->set('Accept', 'text/html');

        /** @var Kernel $kernel */
        $kernel = app(Kernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $location = (string) $response->headers->get('Location', '');

        if ($location === '' && $response->isRedirect()) {
            $location = (string) $response->headers->get('X-Inertia-Location', '');
        }

        $parsed = $location !== ''
            ? $this->parseFacebookOAuthLocation($location)
            : ['location_masked' => null, 'error' => 'No Location header on subrequest response'];

        return array_merge($parsed, [
            'label' => 'B_laravel_kernel_subrequest',
            'http_status' => $response->getStatusCode(),
            'content_type' => (string) $response->headers->get('Content-Type', ''),
            'controller' => MetaOAuthController::class.'::start',
            'status' => $location !== '' ? 'OK' : 'FAIL',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function probeExternalHttp(string $token): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $url = $base.'/oauth/meta/start?token='.urlencode($token);

        try {
            $response = Http::timeout(15)
                ->withOptions(['allow_redirects' => false])
                ->get($url);
        } catch (\Throwable $e) {
            return [
                'label' => 'C_external_http_no_redirect',
                'status' => 'FAIL',
                'error' => $e->getMessage(),
                'request_url_masked' => $base.'/oauth/meta/start?token=[redacted]',
            ];
        }

        $location = (string) $response->header('Location');
        $parsed = $location !== ''
            ? $this->parseFacebookOAuthLocation($location)
            : ['location_masked' => null, 'error' => 'No Location header'];

        $serverHeaders = [];
        foreach (['Server', 'X-Powered-By', 'Via', 'CF-Ray', 'X-Request-Id'] as $header) {
            $value = $response->header($header);
            if ($value !== null && $value !== '') {
                $serverHeaders[$header] = $value;
            }
        }

        return array_merge($parsed, [
            'label' => 'C_external_http_no_redirect',
            'http_status' => $response->status(),
            'content_type' => (string) $response->header('Content-Type'),
            'server_headers' => $serverHeaders,
            'request_url_masked' => $base.'/oauth/meta/start?token=[redacted]',
            'status' => $location !== '' ? 'OK' : 'FAIL',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseFacebookOAuthLocation(string $location): array
    {
        $locationMasked = $this->maskOAuthLocation($location);
        $parts = parse_url($location);
        $query = [];
        if (is_array($parts) && isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $extrasRaw = isset($query['extras']) ? urldecode((string) $query['extras']) : '';
        $extrasDecoded = json_decode($extrasRaw, true);
        $extrasDecoded = is_array($extrasDecoded) ? $extrasDecoded : [];

        $redirectUri = (string) ($query['redirect_uri'] ?? '');
        $redirectParts = parse_url($redirectUri);

        return [
            'location_masked' => $locationMasked,
            'config_id' => (string) ($query['config_id'] ?? ''),
            'response_type' => (string) ($query['response_type'] ?? ''),
            'redirect_uri_host' => (string) ($redirectParts['host'] ?? ''),
            'redirect_uri_path' => (string) ($redirectParts['path'] ?? ''),
            'extras_raw' => $extrasRaw,
            'extras_decoded' => $extrasDecoded,
            'has_version_v4_only' => $extrasDecoded === ['version' => 'v4'],
            'has_featureType' => ($extrasDecoded['featureType'] ?? '') === 'whatsapp_business_app_onboarding',
            'has_sessionInfoVersion_3' => (string) ($extrasDecoded['sessionInfoVersion'] ?? '') === '3',
        ];
    }

    private function maskOAuthLocation(string $location): string
    {
        return (string) preg_replace('/([?&]state=)[^&]+/i', '$1[redacted]', $location);
    }

    /**
     * @param  array<string, mixed>|null  $a
     * @param  array<string, mixed>|null  $b
     * @param  array<string, mixed>|null  $c
     * @return array<string, mixed>
     */
    private function compareProbes(?array $a, ?array $b, ?array $c): array
    {
        $rows = [];
        foreach ([
            'A' => $a,
            'B_kernel' => $b,
            'C_http' => $c,
        ] as $key => $row) {
            if ($row === null) {
                continue;
            }
            $rows[$key] = [
                'has_version_v4_only' => $row['has_version_v4_only'] ?? null,
                'has_featureType' => $row['has_featureType'] ?? null,
                'has_sessionInfoVersion_3' => $row['has_sessionInfoVersion_3'] ?? null,
                'config_id' => $row['config_id'] ?? null,
            ];
        }

        $aOk = ($a['has_featureType'] ?? false) && ($a['has_sessionInfoVersion_3'] ?? false) && ! ($a['has_version_v4_only'] ?? false);
        $bOk = ($b['has_featureType'] ?? false) && ($b['has_sessionInfoVersion_3'] ?? false) && ! ($b['has_version_v4_only'] ?? false);
        $cOk = ($c['has_featureType'] ?? false) && ($c['has_sessionInfoVersion_3'] ?? false) && ! ($c['has_version_v4_only'] ?? false);

        $bMatchesA = $bOk === $aOk && ($b['extras_raw'] ?? '') === ($a['extras_raw'] ?? '');
        $cMatchesA = $cOk === $aOk && ($c['extras_raw'] ?? '') === ($a['extras_raw'] ?? '');

        return [
            'by_probe' => $rows,
            'A_correct' => $aOk,
            'B_matches_A' => $bMatchesA,
            'C_matches_A' => $cMatchesA,
            'B_emits_v4_only' => (bool) ($b['has_version_v4_only'] ?? false),
            'C_emits_v4_only' => (bool) ($c['has_version_v4_only'] ?? false),
        ];
    }

    /**
     * @param  array<string, mixed>  $comparison
     * @param  array<string, mixed>  $runtime
     * @return array{code: string, message: string}
     */
    private function conclude(array $comparison, array $runtime): array
    {
        $aOk = (bool) ($comparison['A_correct'] ?? false);
        $bOk = (bool) ($comparison['B_matches_A'] ?? false);
        $cOk = (bool) ($comparison['C_matches_A'] ?? false);
        $cV4 = (bool) ($comparison['C_emits_v4_only'] ?? false);
        $bV4 = (bool) ($comparison['B_emits_v4_only'] ?? false);

        if ($aOk && $bOk && $cOk) {
            return [
                'code' => 'A',
                'message' => 'Direct authorizationUrl(), Laravel subrequest to /oauth/meta/start, and external HTTP all agree — Business App extras, not version=v4 only. A prior browser capture of version=v4 likely came from an older deploy, a different origin, or a different button flow (not this controller path today).',
            ];
        }

        if ($aOk && (! $bOk || ! $cOk) && ($bV4 || $cV4)) {
            $hint = [];
            if ($cV4 && ! $bV4) {
                $hint[] = 'external HTTP differs from in-process kernel (possible second origin, proxy, or hostname not served by this base_path)';
            }
            if ($bV4) {
                $hint[] = 'kernel subrequest still emits v4 — check OPcache/route cache and MetaEmbeddedSignupService file on disk';
            }
            if (($runtime['meta_oauth_controller_file'] ?? '') !== ($runtime['meta_embedded_signup_service_file'] ?? '')) {
                // noop
            }

            return [
                'code' => 'B',
                'message' => 'Direct PHP authorizationUrl() is correct but HTTP /oauth/meta/start path differs (version=v4 detected). '.implode(' ', $hint),
            ];
        }

        if ($aOk && (! $bOk || ! $cOk)) {
            return [
                'code' => 'B',
                'message' => 'Direct PHP authorizationUrl() differs from HTTP/subrequest Location extras — trace controller binding, route cache, and duplicate installs.',
            ];
        }

        return [
            'code' => 'C',
            'message' => 'Runtime path or code copy mismatch suspected — compare base_path, controller __FILE__, and external server headers.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runtimePaths(): array
    {
        $controller = new \ReflectionClass(MetaOAuthController::class);
        $service = new \ReflectionClass(MetaEmbeddedSignupService::class);
        $extras = new \ReflectionClass(MetaEmbeddedSignupExtras::class);

        return [
            'base_path' => base_path(),
            'public_path' => public_path(),
            'meta_oauth_controller_file' => $controller->getFileName(),
            'meta_embedded_signup_service_file' => $service->getFileName(),
            'meta_embedded_signup_extras_file' => $extras->getFileName(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'php_pid' => function_exists('getmypid') ? getmypid() : null,
            'opcache_enabled' => filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOL),
            'opcache_validate_timestamps' => ini_get('opcache.validate_timestamps'),
            'opcache_revalidate_freq' => ini_get('opcache.revalidate_freq'),
            'git_head' => $this->gitHead(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function routeInfo(): array
    {
        $action = null;
        $middleware = [];
        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            if ($route->uri() !== 'oauth/meta/start') {
                continue;
            }
            $action = $route->getActionName();
            $middleware = $route->gatherMiddleware();
            break;
        }

        return [
            'uri' => 'oauth/meta/start',
            'action' => $action,
            'middleware' => $middleware,
            'named_route' => 'oauth.meta.start',
        ];
    }

    private function gitHead(): string
    {
        $base = base_path();
        if (! is_dir($base.'/.git')) {
            return '';
        }
        $cmd = 'git -C '.escapeshellarg($base).' rev-parse HEAD 2>&1';
        $out = shell_exec($cmd);

        return is_string($out) ? trim($out) : '';
    }
}
