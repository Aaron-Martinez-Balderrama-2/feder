<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'module_id',
        'class_id',
        'title',
        'resource_type',
        'file_path',
        'external_url',
        'file_size_bytes',
        'bandwidth_tier',
        'offline_summary',
        'uploaded_by',
        'download_count',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'download_count' => 'integer',
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

    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'class_id');
    }

    public function class(): BelongsTo
    {
        return $this->courseClass();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
