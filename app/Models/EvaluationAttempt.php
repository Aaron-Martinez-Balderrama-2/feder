<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationAttempt extends Model
{
    protected $fillable = [
        'evaluation_id',
        'user_id',
        'answers',
        'score',
        'max_score',
        'percentage',
        'passed',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'score' => 'float',
            'max_score' => 'float',
            'percentage' => 'float',
            'passed' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(ModuleEvaluation::class, 'evaluation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
