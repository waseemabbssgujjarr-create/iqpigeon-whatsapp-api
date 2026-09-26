<?php

namespace App\Support;

use App\Models\EmbeddedSignupSession;
use App\Models\User;
use App\Models\WhatsappConnection;
use App\Services\Meta\MetaEmbeddedSignupService;
use Illuminate\Support\Facades\Route;

/**
 * Read-only live diagnostics for Meta Embedded Signup / coexistence (temporary debugging).
 */
final class MetaEmbeddedSignupDiagnosticReport
{
    private const EXPECTED_APP_ID = '552479924130015';

    private const EXPECTED_STANDARD_CONFIG = '1647730086942089';

    private const EXPECTED_COEXISTENCE_CONFIG = '97624893834457';

    /**
     * @return array{
     *     summary: list<array{check: string, status: string, result: string}>,
     *     sections: array<string, mixed>,
     *     conclusion: array{code: string, message: string}
     * }
     */
    public function build(?User $user): array
    {
        $sections = [];
        $summary = [];

        $this->checkRuntimeConfig($sections, $summary);
        $this->checkConfigCache($sections, $summary);
        $this->checkExtrasEncoder($sections, $summary);
        $this->checkOAuthBuilderSource($sections, $summary);
        $this->checkGeneratedOAuthUrl($sections, $summary);
        $this->checkFrontendManifest($sections, $summary);
        $this->checkLiveHtmlAsset($sections, $summary);
        $this->checkFbLoginPath($sections, $summary);
        $this->checkRoutes($sections, $summary);
        $this->checkDatabaseDrafts($user, $sections, $summary);
        $this->checkDraftClassification($sections, $summary);
        $this->checkOpcache($sections, $summary);
        $this->checkFileTimestamps($sections, $summary);
        $this->checkGitVersion($sections, $summary);
        $this->checkSourceSignatures($sections, $summary);

        $conclusion = $this->conclude($summary, $sections);

        return [
            'summary' => $summary,
            'sections' => $sections,
            'conclusion' => $conclusion,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkRuntimeConfig(array &$sections, array &$summary): void
    {
        $envNames = [
            'META_APP_ID' => $this->maskConfigValue((string) env('META_APP_ID', '')),
            'META_GRAPH_VERSION' => (string) env('META_GRAPH_VERSION', ''),
            'META_CONFIG_ID / META_ES_CONFIG_ID' => $this->maskConfigValue((string) env('META_CONFIG_ID', env('META_ES_CONFIG_ID', ''))),
            'META_ES_CONFIG_ID_COEXISTENCE' => $this->maskConfigValue((string) env('META_ES_CONFIG_ID_COEXISTENCE', env('META_CONFIG_ID_COEXISTENCE', ''))),
            'APP_URL' => (string) config('app.url'),
        ];

        $appIdRaw = (string) config('services.meta.app_id');
        $coexistRaw = (string) (config('services.meta.es_config_id_coexistence') ?: config('services.meta.es_config_id'));

        $passApp = $appIdRaw === self::EXPECTED_APP_ID;
        $passCoexist = $coexistRaw === self::EXPECTED_COEXISTENCE_CONFIG;

        $sections['check1_env'] = [
            'env_display' => $envNames,
            'note' => 'Secrets (META_APP_SECRET, tokens, keys) are never shown.',
        ];

        $summary[] = [
            'check' => 'Runtime App ID (env display)',
            'status' => $passApp ? 'PASS' : 'FAIL',
            'result' => $passApp ? self::EXPECTED_APP_ID : 'Got '.$this->maskConfigValue($appIdRaw),
        ];
        $summary[] = [
            'check' => 'Coexistence config (runtime)',
            'status' => $passCoexist ? 'PASS' : 'WARN',
            'result' => $passCoexist ? self::EXPECTED_COEXISTENCE_CONFIG : 'Got '.$this->maskConfigValue($coexistRaw),
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkConfigCache(array &$sections, array &$summary): void
    {
        $rows = [
            'app_id' => [
                'runtime' => (string) config('services.meta.app_id'),
                'expected' => self::EXPECTED_APP_ID,
            ],
            'graph_version' => [
                'runtime' => ltrim((string) config('services.meta.graph_version', 'v21.0'), '/'),
                'expected' => '(configured — e.g. v25.0)',
            ],
            'es_config_id' => [
                'runtime' => (string) config('services.meta.es_config_id'),
                'expected' => self::EXPECTED_STANDARD_CONFIG,
            ],
            'es_config_id_coexistence' => [
                'runtime' => (string) (config('services.meta.es_config_id_coexistence') ?: config('services.meta.es_config_id')),
                'expected' => self::EXPECTED_COEXISTENCE_CONFIG,
            ],
        ];

        $allPass = true;
        $display = [];
        foreach ($rows as $key => $row) {
            $pass = $key === 'graph_version'
                ? $row['runtime'] !== ''
                : $row['runtime'] === $row['expected'];
            if (! $pass) {
                $allPass = false;
            }
            $display[$key] = [
                'runtime' => $this->maskConfigValue($row['runtime']),
                'expected' => $row['expected'],
                'status' => $pass ? 'PASS' : 'FAIL',
            ];
        }

        $sections['check2_config_cache'] = $display;
        $summary[] = [
            'check' => 'Laravel config() cache',
            'status' => $allPass ? 'PASS' : 'FAIL',
            'result' => $allPass ? 'config() matches expected IDs' : 'Mismatch — run config:clear / config:cache',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkExtrasEncoder(array &$sections, array &$summary): void
    {
        $json = MetaEmbeddedSignupExtras::encodeBusinessAppCoexistence();
        $decoded = json_decode($json, true);
        $decoded = is_array($decoded) ? $decoded : [];

        $pass = isset($decoded['setup'])
            && ($decoded['featureType'] ?? '') === 'whatsapp_business_app_onboarding'
            && (string) ($decoded['sessionInfoVersion'] ?? '') === '3'
            && ! array_key_exists('version', $decoded)
            && ! isset($decoded['features']);

        $sections['check3_encoder'] = [
            'json' => $json,
            'decoded' => $decoded,
            'status' => $pass ? 'PASS' : 'FAIL',
        ];

        $summary[] = [
            'check' => 'Extras encoder',
            'status' => $pass ? 'PASS' : 'FAIL',
            'result' => $pass ? 'Business App extras OK' : 'Unexpected encoder output',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkOAuthBuilderSource(array &$sections, array &$summary): void
    {
        $path = app_path('Services/Meta/MetaEmbeddedSignupService.php');
        $source = is_readable($path) ? (string) file_get_contents($path) : '';

        $usesEncoder = str_contains($source, 'encodeBusinessAppCoexistence()');
        $coexistenceBranch = preg_match(
            '/FLOW_COEXISTENCE[\s\S]{0,400}encodeBusinessAppCoexistence\(\)/',
            $source,
        ) === 1;
        $badCoexistenceV4 = preg_match(
            '/FLOW_COEXISTENCE[\s\S]{0,400}v4Default\(\)/',
            $source,
        ) === 1;

        $pass = $usesEncoder && $coexistenceBranch && ! $badCoexistenceV4;

        $sections['check4_oauth_builder'] = [
            'class' => MetaEmbeddedSignupService::class,
            'method' => 'authorizationUrl',
            'uses_encodeBusinessAppCoexistence' => $usesEncoder,
            'coexistence_branch_uses_encoder' => $coexistenceBranch,
            'coexistence_branch_uses_v4Default' => $badCoexistenceV4,
            'status' => $pass ? 'PASS' : 'FAIL',
        ];

        $summary[] = [
            'check' => 'PHP OAuth builder source',
            'status' => $pass ? 'PASS' : 'FAIL',
            'result' => $pass ? 'Coexistence uses encodeBusinessAppCoexistence()' : 'Coexistence may still use v4Default()',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkGeneratedOAuthUrl(array &$sections, array &$summary): void
    {
        $connection = new WhatsappConnection([
            'metadata' => ['onboarding_source' => 'coexistence'],
        ]);
        $session = new EmbeddedSignupSession;
        $session->setRelation('whatsappConnection', $connection);

        $url = app(MetaEmbeddedSignupService::class)->authorizationUrl($session, 'diagnostic-non-persistent-token');

        $parsed = parse_url($url);
        parse_str((string) ($parsed['query'] ?? ''), $query);

        $extrasRaw = isset($query['extras']) ? urldecode((string) $query['extras']) : '';
        $extrasDecoded = json_decode($extrasRaw, true);
        $extrasDecoded = is_array($extrasDecoded) ? $extrasDecoded : [];

        $onlyV4 = $extrasDecoded === ['version' => 'v4']
            || (count($extrasDecoded) === 1 && ($extrasDecoded['version'] ?? null) === 'v4');

        $passExtras = ($extrasDecoded['featureType'] ?? '') === 'whatsapp_business_app_onboarding'
            && (string) ($extrasDecoded['sessionInfoVersion'] ?? '') === '3'
            && ! array_key_exists('version', $extrasDecoded);

        $passConfig = ($query['config_id'] ?? '') === self::EXPECTED_COEXISTENCE_CONFIG;
        $passApp = ($query['client_id'] ?? '') === self::EXPECTED_APP_ID;

        $sections['check5_oauth_url'] = [
            'params' => [
                'app_id' => $this->maskConfigValue((string) ($query['client_id'] ?? '')),
                'config_id' => $this->maskConfigValue((string) ($query['config_id'] ?? '')),
                'response_type' => (string) ($query['response_type'] ?? ''),
                'redirect_uri' => (string) ($query['redirect_uri'] ?? ''),
                'extras' => $extrasRaw,
                'graph_path_version' => ltrim((string) ($parsed['path'] ?? ''), '/'),
            ],
            'php_redirect_flow_emits_version_v4_only' => $onlyV4 ? 'YES' : 'NO',
            'status' => ($passExtras && $passConfig && $passApp && ! $onlyV4) ? 'PASS' : 'FAIL',
        ];

        $summary[] = [
            'check' => 'Generated coexistence OAuth URL',
            'status' => ($passExtras && ! $onlyV4) ? 'PASS' : 'FAIL',
            'result' => $onlyV4 ? 'extras is version=v4 only' : ($passExtras ? 'Business App extras in URL' : 'Unexpected extras'),
        ];
        $summary[] = [
            'check' => 'PHP redirect flow emits version=v4',
            'status' => $onlyV4 ? 'FAIL' : 'PASS',
            'result' => $onlyV4 ? 'YES' : 'NO',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkFrontendManifest(array &$sections, array &$summary): void
    {
        $manifestPath = public_path('build/manifest.json');
        $manifest = [];
        if (is_readable($manifestPath)) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            $manifest = is_array($manifest) ? $manifest : [];
        }

        $entryKey = 'resources/js/app.jsx';
        $jsFile = (string) data_get($manifest, "{$entryKey}.file", '');
        $jsPath = $jsFile !== '' ? public_path('build/'.$jsFile) : '';
        $jsSource = ($jsPath !== '' && is_readable($jsPath)) ? (string) file_get_contents($jsPath) : '';

        $hasFeature = str_contains($jsSource, 'whatsapp_business_app_onboarding');
        $hasSession = str_contains($jsSource, 'sessionInfoVersion');
        $hasV4 = preg_match('/version:\s*[`\'"]v4[`\'"]/', $jsSource) === 1
            || str_contains($jsSource, 'version:"v4"');

        $pass = $hasFeature && $hasSession && ! $hasV4 && $jsFile !== '';

        $sections['check6_manifest'] = [
            'manifest_path' => $manifestPath,
            'manifest_mtime' => is_readable($manifestPath) ? date('c', (int) filemtime($manifestPath)) : null,
            'js_asset' => $jsFile,
            'js_path' => $jsPath,
            'featureType_present' => $hasFeature,
            'sessionInfoVersion_present' => $hasSession,
            'version_v4_present' => $hasV4,
            'status' => $pass ? 'PASS' : 'FAIL',
        ];

        $summary[] = [
            'check' => 'Compiled JS (manifest)',
            'status' => $pass ? 'PASS' : 'FAIL',
            'result' => $jsFile !== '' ? basename($jsFile) : 'manifest entry missing',
        ];
        $summary[] = [
            'check' => 'JS sessionInfoVersion',
            'status' => $hasSession ? 'PASS' : 'FAIL',
            'result' => $hasSession ? 'present' : 'missing',
        ];
        $summary[] = [
            'check' => 'JS v4 payload',
            'status' => $hasV4 ? 'FAIL' : 'PASS',
            'result' => $hasV4 ? 'version:v4 found in bundle' : 'not found',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkLiveHtmlAsset(array &$sections, array &$summary): void
    {
        $manifestPath = public_path('build/manifest.json');
        $manifest = is_readable($manifestPath)
            ? json_decode((string) file_get_contents($manifestPath), true)
            : [];
        $manifest = is_array($manifest) ? $manifest : [];
        $expectedJs = (string) data_get($manifest, 'resources/js/app.jsx.file', '');

        $htmlJs = null;
        $staleLikely = null;
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(8)
                ->get(rtrim((string) config('app.url'), '/').'/login');
            if ($response->successful()) {
                if (preg_match('/\/build\/(assets\/app-[A-Za-z0-9_-]+\.js)/', (string) $response->body(), $m)) {
                    $htmlJs = $m[1];
                }
            }
        } catch (\Throwable) {
            $htmlJs = '(could not fetch login HTML)';
        }

        $match = $expectedJs !== '' && $htmlJs === $expectedJs;
        $staleLikely = $expectedJs !== '' && is_string($htmlJs) && str_starts_with($htmlJs, 'assets/') && ! $match;

        $sections['check7_live_html'] = [
            'manifest_js_entry' => $expectedJs,
            'login_page_js_reference' => $htmlJs,
            'matches_manifest' => $match,
            'stale_asset_likely' => $staleLikely,
            'status' => $match ? 'PASS' : ($staleLikely ? 'WARN' : 'WARN'),
        ];

        $summary[] = [
            'check' => 'Live HTML vs manifest',
            'status' => $match ? 'PASS' : 'WARN',
            'result' => $match ? 'Same JS asset' : 'Possible stale HTML or fetch issue',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkFbLoginPath(array &$sections, array &$summary): void
    {
        $manifestPath = public_path('build/manifest.json');
        $manifest = is_readable($manifestPath)
            ? json_decode((string) file_get_contents($manifestPath), true)
            : [];
        $jsFile = (string) data_get($manifest, 'resources/js/app.jsx.file', '');
        $jsPath = $jsFile !== '' ? public_path('build/'.$jsFile) : '';
        $jsSource = ($jsPath !== '' && is_readable($jsPath)) ? (string) file_get_contents($jsPath) : '';

        $hasFbLogin = str_contains($jsSource, 'FB.login');
        $hasCoexistenceExtras = str_contains($jsSource, 'COEXISTENCE_EMBEDDED_SIGNUP_EXTRAS')
            || (str_contains($jsSource, 'whatsapp_business_app_onboarding') && str_contains($jsSource, 'sessionInfoVersion'));

        $pass = $hasFbLogin && $hasCoexistenceExtras;

        $sections['check8_fb_login'] = [
            'js_asset' => $jsFile,
            'FB.login_present' => $hasFbLogin,
            'coexistence_extras_constant_present' => $hasCoexistenceExtras,
            'expected_config_id' => self::EXPECTED_COEXISTENCE_CONFIG,
            'note' => 'config_id is injected at runtime from Inertia metaEmbeddedSignup.config_id_coexistence',
            'status' => $pass ? 'PASS' : 'FAIL',
        ];

        $summary[] = [
            'check' => 'FB.login JS path',
            'status' => $pass ? 'PASS' : 'FAIL',
            'result' => $pass ? 'Coexistence extras in compiled bundle' : 'Missing FB.login or coexistence extras',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkRoutes(array &$sections, array &$summary): void
    {
        $wanted = [
            'POST app/connections/start-coexistence' => ['method' => 'POST', 'uri' => 'app/connections/start-coexistence'],
            'GET oauth/meta/start' => ['method' => 'GET', 'uri' => 'oauth/meta/start'],
            'POST app/connections/{uuid}/continue' => ['method' => 'POST', 'uri' => 'app/connections/{uuid}/continue'],
        ];

        $found = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            $methods = $route->methods();
            $action = $route->getActionName();
            foreach ($wanted as $label => $spec) {
                if (in_array($spec['method'], $methods, true) && $uri === $spec['uri']) {
                    $found[$label] = $action;
                }
            }
        }

        $pass = count($found) === count($wanted);

        $sections['check9_routes'] = [
            'routes' => $found,
            'flow_notes' => [
                'primary_coexistence' => 'POST start-coexistence → session flash → FB.login (no oauth/meta/start)',
                'oauth_redirect' => 'GET oauth/meta/start → MetaEmbeddedSignupService::authorizationUrl → facebook dialog/oauth',
                'continue_setup' => 'POST continue → coexistence flash OR redirect to oauth/meta/start (depends on onboarding_source)',
            ],
            'status' => $pass ? 'PASS' : 'WARN',
        ];

        $summary[] = [
            'check' => 'Routes',
            'status' => $pass ? 'PASS' : 'WARN',
            'result' => $pass ? 'All three routes resolved' : 'Some routes missing',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkDatabaseDrafts(?User $user, array &$sections, array &$summary): void
    {
        $connections = [];
        $sessions = [];

        $partnerId = $user?->partner_id;
        if ($partnerId) {
            $connections = WhatsappConnection::query()
                ->where('partner_id', $partnerId)
                ->whereIn('connection_status', ['pending', 'error'])
                ->orderByDesc('id')
                ->limit(20)
                ->get(['uuid', 'connection_status', 'metadata', 'created_at', 'updated_at'])
                ->map(function (WhatsappConnection $c) {
                    $meta = is_array($c->metadata) ? $c->metadata : [];

                    return [
                        'uuid' => $c->uuid,
                        'connection_status' => $c->connection_status?->value ?? (string) $c->connection_status,
                        'onboarding_source' => (string) ($meta['onboarding_source'] ?? ''),
                        'created_at' => $c->created_at?->toIso8601String(),
                        'updated_at' => $c->updated_at?->toIso8601String(),
                    ];
                })
                ->all();

            $connectionIds = WhatsappConnection::query()
                ->where('partner_id', $partnerId)
                ->pluck('id');

            if ($connectionIds->isNotEmpty()) {
                $sessions = EmbeddedSignupSession::query()
                    ->whereIn('whatsapp_connection_id', $connectionIds)
                    ->orderByDesc('id')
                    ->limit(20)
                    ->get(['id', 'whatsapp_connection_id', 'status', 'metadata', 'created_at', 'updated_at'])
                    ->map(function (EmbeddedSignupSession $s) {
                        $meta = is_array($s->metadata) ? $s->metadata : [];
                        $returnUrl = isset($meta['return_url']) ? (string) $meta['return_url'] : '';

                        return [
                            'id' => $s->id,
                            'whatsapp_connection_id' => $s->whatsapp_connection_id,
                            'status' => (string) $s->status,
                            'return_url' => $returnUrl !== '' ? $this->maskUrl($returnUrl) : null,
                            'created_at' => $s->created_at?->toIso8601String(),
                            'updated_at' => $s->updated_at?->toIso8601String(),
                        ];
                    })
                    ->all();
            }
        }

        $sections['check10_database'] = [
            'partner_scoped' => $partnerId !== null,
            'whatsapp_connections' => $connections,
            'embedded_signup_sessions' => $sessions,
            'note' => 'No tokens, state hashes, or secrets are displayed.',
        ];

        $summary[] = [
            'check' => 'Database drafts (scoped)',
            'status' => 'PASS',
            'result' => $partnerId ? count($connections).' incomplete connection(s)' : 'No partner on user — drafts hidden',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkDraftClassification(array &$sections, array &$summary): void
    {
        $drafts = $sections['check10_database']['whatsapp_connections'] ?? [];
        $classified = [];
        foreach ($drafts as $draft) {
            $source = (string) ($draft['onboarding_source'] ?? '');
            $classification = match ($source) {
                'coexistence' => 'COEXISTENCE',
                'standard' => 'STANDARD',
                default => 'UNKNOWN',
            };
            $classified[] = [
                'uuid' => $draft['uuid'],
                'classification' => $classification,
                'onboarding_source' => $source === '' ? '(empty)' : $source,
            ];
        }

        $sections['check11_classification'] = $classified;
        $summary[] = [
            'check' => 'Draft classification',
            'status' => 'PASS',
            'result' => count($classified).' draft(s) classified',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkOpcache(array &$sections, array &$summary): void
    {
        $sections['check12_opcache'] = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'opcache_enabled' => filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOL),
            'opcache_validate_timestamps' => ini_get('opcache.validate_timestamps'),
            'opcache_revalidate_freq' => ini_get('opcache.revalidate_freq'),
        ];

        $summary[] = [
            'check' => 'PHP / OPcache',
            'status' => 'PASS',
            'result' => 'PHP '.PHP_VERSION.' — informational',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkFileTimestamps(array &$sections, array &$summary): void
    {
        $jsAsset = (string) data_get($sections, 'check6_manifest.js_asset', '');
        $files = [
            'MetaEmbeddedSignupExtras.php' => app_path('Support/MetaEmbeddedSignupExtras.php'),
            'MetaEmbeddedSignupService.php' => app_path('Services/Meta/MetaEmbeddedSignupService.php'),
            'metaEmbeddedSignup.js' => resource_path('js/lib/metaEmbeddedSignup.js'),
            'manifest.json' => public_path('build/manifest.json'),
            'main_js_bundle' => $jsAsset !== '' ? public_path('build/'.$jsAsset) : '',
        ];

        $mtimes = [];
        foreach ($files as $label => $path) {
            if ($path !== '' && is_readable($path)) {
                $mtimes[$label] = date('c', (int) filemtime($path));
            } else {
                $mtimes[$label] = '(missing)';
            }
        }

        $sections['check13_mtimes'] = $mtimes;
        $summary[] = [
            'check' => 'File timestamps',
            'status' => 'PASS',
            'result' => 'See section for mtimes',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkGitVersion(array &$sections, array &$summary): void
    {
        $head = $this->runGit(['rev-parse', 'HEAD']);
        $log = $this->runGit(['log', '-3', '--oneline']);

        $expected = ['3d7da97', '31fac5d', '5b85709'];
        $headShort = substr(trim($head), 0, 7);
        $pass = str_starts_with(trim($head), '3d7da97') || $headShort === '3d7da97';

        $sections['check14_git'] = [
            'HEAD' => trim($head),
            'log' => trim($log),
            'expected_commits_present_in_log' => array_map(
                fn (string $c) => str_contains($log, $c) ? 'yes' : 'no',
                $expected,
            ),
            'status' => $pass ? 'PASS' : 'WARN',
        ];

        $summary[] = [
            'check' => 'Git commit',
            'status' => $pass ? 'PASS' : 'WARN',
            'result' => trim($head) !== '' ? substr(trim($head), 0, 12) : 'git unavailable',
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  list<array{check: string, status: string, result: string}>  $summary
     */
    private function checkSourceSignatures(array &$sections, array &$summary): void
    {
        $files = [
            app_path('Support/MetaEmbeddedSignupExtras.php'),
            app_path('Services/Meta/MetaEmbeddedSignupService.php'),
            resource_path('js/lib/metaEmbeddedSignup.js'),
            app_path('Http/Controllers/App/ConnectionController.php'),
            base_path('routes/web.php'),
        ];

        $needles = [
            'whatsapp_business_app_onboarding',
            'sessionInfoVersion',
            'v4Default',
            'encodeBusinessAppCoexistence',
            'start-coexistence',
            '/oauth/meta/start',
        ];

        $hits = [];
        foreach ($files as $file) {
            if (! is_readable($file)) {
                continue;
            }
            $source = (string) file_get_contents($file);
            foreach ($needles as $needle) {
                if (! str_contains($source, $needle)) {
                    continue;
                }
                $pos = strpos($source, $needle);
                $snippet = substr($source, max(0, $pos - 40), min(120, strlen($source) - max(0, $pos - 40)));
                $snippet = preg_replace('/\s+/', ' ', $snippet) ?? $snippet;
                $hits[] = [
                    'file' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $file),
                    'needle' => $needle,
                    'snippet' => trim($snippet),
                ];
            }
        }

        $sections['check15_signatures'] = $hits;
        $summary[] = [
            'check' => 'Source signatures',
            'status' => 'PASS',
            'result' => count($hits).' hit(s)',
        ];
    }

    /**
     * @param  list<array{check: string, status: string, result: string}>  $summary
     * @param  array<string, mixed>  $sections
     * @return array{code: string, message: string}
     */
    private function conclude(array $summary, array $sections): array
    {
        $failures = array_filter($summary, fn (array $row) => $row['status'] === 'FAIL');
        $phpV4Only = ($sections['check5_oauth_url']['php_redirect_flow_emits_version_v4_only'] ?? '') === 'YES';
        $jsHasV4 = (bool) data_get($sections, 'check6_manifest.version_v4_present', false);

        if ($failures !== []) {
            $labels = implode(', ', array_map(fn (array $r) => $r['check'], $failures));

            return [
                'code' => 'B',
                'message' => 'Application-side mismatch detected: '.$labels,
            ];
        }

        if ($phpV4Only || $jsHasV4) {
            return [
                'code' => 'C',
                'message' => 'Production appears to be serving stale or inconsistent PHP/JS (v4-only redirect or v4 in bundle).',
            ];
        }

        return [
            'code' => 'A',
            'message' => 'Application-side configuration/code appears internally consistent. Primary coexistence FB.login and PHP redirect builder (in-memory session) emit Business App extras. If Meta still shows Login Error, compare network: POST start-coexistence without GET oauth/meta/start vs Continue setup on STANDARD/UNKNOWN drafts.',
        ];
    }

    private function maskConfigValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '(empty)';
        }
        if (strlen($value) <= 8) {
            return $value;
        }

        return substr($value, 0, 4).'…'.substr($value, -4);
    }

    private function maskUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return '(invalid url)';
        }
        $host = (string) ($parts['host'] ?? '');
        $path = (string) ($parts['path'] ?? '');

        return ($parts['scheme'] ?? 'https').'://'.$host.$path.(isset($parts['query']) ? '?…' : '');
    }

    /**
     * @param  list<string>  $args
     */
    private function runGit(array $args): string
    {
        $base = base_path();
        if (! is_dir($base.'/.git')) {
            return '';
        }

        $cmd = array_merge(['git', '-C', $base], $args);
        $escaped = array_map('escapeshellarg', $cmd);
        $command = implode(' ', $escaped).' 2>&1';
        $output = @shell_exec($command);

        return is_string($output) ? $output : '';
    }
}
