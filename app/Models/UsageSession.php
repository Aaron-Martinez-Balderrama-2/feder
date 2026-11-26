<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageSession extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'session_date',
        'seconds_spent',
        'pings_count',
        'last_path',
        'last_ping_at',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'seconds_spent' => 'integer',
            'pings_count' => 'integer',
            'last_ping_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
