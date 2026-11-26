<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email_attempted',
        'event_type',
        'resource',
        'action_detail',
        'ip_address',
        'user_agent',
        'http_method',
        'route_path',
        'status_code',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $eventType,
        string $resource,
        ?string $detail = null,
        ?int $userId = null,
        ?string $emailAttempted = null,
        int $statusCode = 200
    ): self {
        $req = request();
        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'email_attempted' => $emailAttempted,
            'event_type' => $eventType,
            'resource' => $resource,
            'action_detail' => $detail,
            'ip_address' => $req?->ip(),
            'user_agent' => mb_substr((string) ($req?->userAgent() ?? ''), 0, 250),
            'http_method' => $req?->method(),
            'route_path' => mb_substr('/' . ltrim((string) ($req?->path() ?? ''), '/'), 0, 195),
            'status_code' => $statusCode,
            'created_at' => now(),
        ]);
    }
}
