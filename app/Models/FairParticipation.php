<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FairParticipation extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'enrollment_id',
        'project_title',
        'stand_code',
        'fair_date',
        'fair_location',
        'rubric_score',
        'participation_status',
        'evaluator_feedback',
        'evaluated_by',
    ];

    protected function casts(): array
    {
        return [
            'fair_date' => 'date',
            'rubric_score' => 'float',
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

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }
}
