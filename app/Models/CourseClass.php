<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'description',
        'position',
        'scheduled_at',
        'attendance_opens_at',
        'attendance_expires_at',
        'attendance_code',
        'summary_light_text',
        'is_completed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_completed' => 'boolean',
            'scheduled_at' => 'datetime',
            'attendance_opens_at' => 'datetime',
            'attendance_expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(CourseMaterial::class, 'class_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CourseAttendance::class, 'class_id');
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class, 'class_id');
    }

    public function getIsAttendanceActiveAttribute(): bool
    {
        if (!$this->attendance_expires_at || !$this->attendance_expires_at->isFuture()) {
            return false;
        }
        if ($this->attendance_opens_at && $this->attendance_opens_at->isFuture()) {
            return false;
        }
        return true;
    }
}
