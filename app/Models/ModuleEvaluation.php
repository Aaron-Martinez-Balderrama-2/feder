<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleEvaluation extends Model
{
    protected $fillable = [
        'course_id',
        'module_id',
        'title',
        'instructions',
        'passing_score',
        'max_attempts',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'passing_score' => 'float',
            'max_attempts' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(EvaluationQuestion::class, 'evaluation_id')->orderBy('position')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(EvaluationAttempt::class, 'evaluation_id');
    }
}
