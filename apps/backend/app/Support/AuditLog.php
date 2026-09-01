<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLog
{
    public static function record(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?User $actor = null,
    ): void {
        $actor ??= auth('sanctum')->user();

        $ua = request()->userAgent();

        ActivityLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'ip_address' => request()->ip(),
            'user_agent' => $ua !== null ? substr($ua, 0, 255) : null,
        ]);
    }
}