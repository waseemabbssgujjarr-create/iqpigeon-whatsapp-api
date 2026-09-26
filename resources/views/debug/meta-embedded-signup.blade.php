<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IQPigeon — Meta Embedded Signup LIVE Diagnostics</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 1.5rem; background: #0f172a; color: #e2e8f0; line-height: 1.5; }
        h1 { font-size: 1.35rem; margin-bottom: 0.25rem; }
        .banner { background: #7f1d1d; color: #fecaca; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1.25rem; font-weight: 600; }
        .conclusion { background: #1e293b; border: 1px solid #334155; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: 0.875rem; }
        th, td { border: 1px solid #334155; padding: 0.5rem 0.65rem; text-align: left; vertical-align: top; }
        th { background: #1e293b; }
        .PASS { color: #4ade80; font-weight: 600; }
        .WARN { color: #fbbf24; font-weight: 600; }
        .FAIL { color: #f87171; font-weight: 600; }
        section { margin-bottom: 2rem; }
        section h2 { font-size: 1rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
        pre { background: #020617; padding: 0.75rem; border-radius: 0.375rem; overflow-x: auto; font-size: 0.8rem; }
        .muted { color: #94a3b8; font-size: 0.85rem; }
        code { background: #1e293b; padding: 0.1rem 0.35rem; border-radius: 0.25rem; }
    </style>
</head>
<body>
    <div class="banner">READ ONLY — DELETE AFTER DEBUGGING</div>
    <h1>IQPigeon — Meta Embedded Signup LIVE Diagnostics</h1>
    <p class="muted">Generated {{ $report['generated_at'] ?? '' }} · {{ $appUrl }}</p>

    <div class="conclusion">
        <strong>Conclusion ({{ $report['conclusion']['code'] ?? '?' }})</strong>
        <p>{{ $report['conclusion']['message'] ?? '' }}</p>
    </div>

    <section>
        <h2>Summary</h2>
        <table>
            <thead><tr><th>Check</th><th>Status</th><th>Result</th></tr></thead>
            <tbody>
                @foreach ($report['summary'] ?? [] as $row)
                    <tr>
                        <td>{{ $row['check'] }}</td>
                        <td class="{{ $row['status'] }}">{{ $row['status'] }}</td>
                        <td>{{ $row['result'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    @php $s = $report['sections'] ?? []; @endphp

    <section>
        <h2>Check 1 — Env / runtime (masked)</h2>
        <pre>{{ json_encode($s['check1_env'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 2 — Laravel config()</h2>
        <pre>{{ json_encode($s['check2_config_cache'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 3 — encodeBusinessAppCoexistence()</h2>
        <pre>{{ json_encode($s['check3_encoder'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 4 — OAuth builder source</h2>
        <pre>{{ json_encode($s['check4_oauth_builder'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 5 — Generated coexistence OAuth URL (in-memory session)</h2>
        <pre>{{ json_encode($s['check5_oauth_url'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 6 — Frontend manifest / compiled JS</h2>
        <pre>{{ json_encode($s['check6_manifest'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 7 — Live HTML vs manifest</h2>
        <pre>{{ json_encode($s['check7_live_html'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 8 — FB.login JS path</h2>
        <pre>{{ json_encode($s['check8_fb_login'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 9 — Routes</h2>
        <pre>{{ json_encode($s['check9_routes'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 10 — Database drafts (partner-scoped)</h2>
        <pre>{{ json_encode($s['check10_database'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 11 — Draft classification</h2>
        <pre>{{ json_encode($s['check11_classification'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 12 — PHP / OPcache</h2>
        <pre>{{ json_encode($s['check12_opcache'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 13 — File timestamps</h2>
        <pre>{{ json_encode($s['check13_mtimes'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 14 — Git</h2>
        <pre>{{ json_encode($s['check14_git'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Check 15 — Source signatures</h2>
        <pre>{{ json_encode($s['check15_signatures'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Summary (repeat)</h2>
        <table>
            <thead><tr><th>Check</th><th>Status</th><th>Result</th></tr></thead>
            <tbody>
                @foreach ($report['summary'] ?? [] as $row)
                    <tr>
                        <td>{{ $row['check'] }}</td>
                        <td class="{{ $row['status'] }}">{{ $row['status'] }}</td>
                        <td>{{ $row['result'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</body>
</html>
