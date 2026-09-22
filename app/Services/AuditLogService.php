<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $action,
        ?Partner $partner = null,
        ?User $user = null,
        ?Model $subject = null,
        array $properties = [],
        ?string $ip = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'partner_id' => $partner?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $properties,
            'ip' => $ip,
        ]);
    }
}
