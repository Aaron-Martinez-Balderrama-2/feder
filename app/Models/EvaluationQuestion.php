<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationQuestion extends Model
{
    protected $fillable = [
        'evaluation_id',
        'question_text',
        'question_type',
        'options',
        'correct_option',
        'points',
        'explanation',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'points' => 'float',
            'position' => 'integer',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(ModuleEvaluation::class, 'evaluation_id');
    }
}
