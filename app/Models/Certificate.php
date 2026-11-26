<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'verification_code',
        'course_id',
        'user_id',
        'enrollment_id',
        'issued_by',
        'final_grade',
        'attendance_pct',
        'hours_completed',
        'custom_file_path',
        'status',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'final_grade' => 'float',
            'attendance_pct' => 'integer',
            'hours_completed' => 'integer',
            'issued_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
