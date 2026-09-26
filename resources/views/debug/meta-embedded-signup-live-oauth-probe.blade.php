<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meta Embedded Signup — Live OAuth Probe</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 1.5rem; background: #0f172a; color: #e2e8f0; line-height: 1.5; }
        h1 { font-size: 1.25rem; }
        .banner { background: #7f1d1d; color: #fecaca; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-weight: 600; }
        pre { background: #020617; padding: 0.75rem; border-radius: 0.375rem; overflow-x: auto; font-size: 0.78rem; }
        .code-A { color: #4ade80; font-weight: 700; }
        .code-B { color: #fbbf24; font-weight: 700; }
        .code-C { color: #f87171; font-weight: 700; }
        section { margin-bottom: 1.5rem; }
        h2 { font-size: 0.95rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
    </style>
</head>
<body>
    <div class="banner">READ ONLY (Meta) — creates one diagnostic DB draft per load — DELETE AFTER DEBUGGING</div>
    <h1>Live OAuth probe — /oauth/meta/start</h1>
    <p>Generated {{ $probe['generated_at'] ?? '' }} · {{ $appUrl }}</p>
    <p>
        <a href="{{ route('debug.meta-embedded-signup') }}" style="color:#a5b4fc">← Main diagnostics</a>
    </p>

    @php $c = $probe['probe']['conclusion'] ?? ['code' => '?', 'message' => '']; @endphp
    <section>
        <h2>Conclusion</h2>
        <p>
            <span class="code-{{ $c['code'] }}">{{ $c['code'] }}</span>:
            {{ $c['message'] }}
        </p>
    </section>

    <section>
        <h2>Runtime paths</h2>
        <pre>{{ json_encode($probe['runtime'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Route: oauth/meta/start</h2>
        <pre>{{ json_encode($probe['route'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>

    <section>
        <h2>Probe comparison (A direct · B kernel · C external HTTP)</h2>
        <pre>{{ json_encode($probe['probe'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>
</body>
</html>
