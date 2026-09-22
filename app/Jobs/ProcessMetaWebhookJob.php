<?php

namespace App\Jobs;

use App\Models\SystemEvent;
use App\Services\Meta\MetaWebhookProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessMetaWebhookJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $systemEventId,
    ) {}

    public function handle(MetaWebhookProcessor $processor): void
    {
        $event = SystemEvent::query()->find($this->systemEventId);

        if ($event === null) {
            return;
        }

        $processor->processStoredEvent($event);
    }
}
