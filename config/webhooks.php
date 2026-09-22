<?php

$backoffRaw = env('PARTNER_WEBHOOK_BACKOFF_SECONDS', '30,60,120,300');

$backoff = array_values(array_filter(array_map(
    static fn (string $part) => (int) trim($part),
    explode(',', (string) $backoffRaw),
), static fn (int $seconds) => $seconds > 0));

if ($backoff === []) {
    $backoff = [30, 60, 120, 300];
}

return [

    'delivery' => [
        'timeout_seconds' => (int) env('PARTNER_WEBHOOK_TIMEOUT', 15),
        'max_attempts' => max(1, (int) env('PARTNER_WEBHOOK_MAX_ATTEMPTS', 5)),
        'backoff_seconds' => $backoff,
    ],

];
