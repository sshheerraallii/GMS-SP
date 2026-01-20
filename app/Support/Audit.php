<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class Audit
{
    public static function log(
        string $action,
        string $auditableType,
        int $auditableId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null
    ): void {
        $req = $request ?: request();

        AuditLog::create([
            'user_id'        => auth()->id(),
            'action'         => $action,
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'ip'             => $req?->ip(),
            'user_agent'     => substr((string) $req?->userAgent(), 0, 2000),
        ]);
    }
}
