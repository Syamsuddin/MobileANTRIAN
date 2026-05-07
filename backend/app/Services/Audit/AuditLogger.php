<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogger
{
    public function log(
        ?User $actor,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = [],
        ?string $requestId = null
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'request_id' => $requestId,
            'metadata' => array_merge(['source' => 'mobile'], $metadata),
        ]);
    }
}
