<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    use HasFactory;

    public const DEFAULT_PHASES = [
        'Modelo de negocio',
        'Finanzas para la empresa',
        'Marketing Digital',
        'Legal',
    ];

    protected $fillable = [
        'category_id',
        'instructor_id',
        'period_id',
        'title',
        'slug',
        'hook',
        'description',
        'outcome',
        'level',
        'capacity',
        'price',
        'installments_allowed',
        'modality',
        'classroom_location',
        'hours_duration',
        'min_passing_score',
        'min_attendance_pct',
        'requires_fair_participation',
        'low_bandwidth_mode',
        'start_date',
        'end_date',
        'progress_pct',
        'color',
        'cover_image',
        'meet_url',
        'whatsapp_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'capacity' => 'integer',
            'price' => 'float',
            'installments_allowed' => 'integer',
            'hours_duration' => 'integer',
            'min_passing_score' => 'float',
            'min_attendance_pct' => 'integer',
            'requires_fair_participation' => 'boolean',
            'low_bandwidth_mode' => 'boolean',
            'progress_pct' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class, 'period_id');
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_prerequisites', 'course_id', 'prerequisite_course_id')
            ->withPivot('id', 'is_mandatory', 'notes')
            ->withTimestamps();
    }

    public function prerequisiteRecords(): HasMany
    {
        return $this->hasMany(CoursePrerequisite::class, 'course_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('position')->orderBy('id');
    }

    public function classes(): HasManyThrough
    {
        return $this->hasManyThrough(CourseClass::class, CourseModule::class, 'course_id', 'module_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(CourseMaterial::class);
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ModuleEvaluation::class);
    }

    public function fairParticipations(): HasMany
    {
        return $this->hasMany(FairParticipation::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot('id', 'status', 'enrolled_at', 'completed_at')
            ->withTimestamps();
    }

    public function paymentPlans(): HasMany
    {
        return $this->hasMany(PaymentPlan::class);
    }

    public function getTakenSeatsAttribute(): int
    {
        if (isset($this->attributes['enrollments_count'])) {
            return (int) $this->attributes['enrollments_count'];
        }

        if ($this->relationLoaded('enrollments')) {
            return $this->enrollments->whereIn('status', ['active', 'completed'])->count();
        }

        return $this->enrollments()->whereIn('status', ['active', 'completed'])->count();
    }

    public function getAvailableSeatsAttribute(): int
    {
        return max(0, $this->capacity - $this->taken_seats);
    }

    public function getIsOpenAttribute(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if ($this->end_date && $this->end_date->copy()->endOfDay()->isPast()) {
            return false;
        }

        if ($this->progress_pct >= 50) {
            return false;
        }

        return $this->available_seats > 0;
    }

    public function recalculateProgress(): int
    {
        $total = $this->classes()->count();
        $completed = $this->classes()->where('course_classes.is_completed', true)->count();
        $pct = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        $this->update(['progress_pct' => $pct]);
        return $pct;
    }
}
