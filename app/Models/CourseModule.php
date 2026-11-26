<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'position',
        'prerequisite_module_id',
        'weight_pct',
        'is_completed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'weight_pct' => 'integer',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function prerequisiteModule(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'prerequisite_module_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(CourseClass::class, 'module_id')->orderBy('position')->orderBy('id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(CourseMaterial::class, 'module_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ModuleEvaluation::class, 'module_id');
    }
}
