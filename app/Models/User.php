<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'id_document',
        'phone',
        'birth_date',
        'city',
        'education_level',
        'avatar',
        'profile_picture',
        'gender',
        'is_active',
        'total_active_seconds',
        'last_seen_at',
        'preferred_theme',
        'preferred_color_mode',
        'font_scale',
        'high_contrast',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
            'high_contrast' => 'boolean',
            'total_active_seconds' => 'integer',
            'font_scale' => 'float',
            'password' => 'hashed',
        ];
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function entrepreneurProfile(): HasOne
    {
        return $this->hasOne(EntrepreneurProfile::class);
    }

    public function instructedCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot('id', 'status', 'enrolled_at', 'completed_at')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CourseAttendance::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function paymentPlans(): HasMany
    {
        return $this->hasMany(PaymentPlan::class);
    }

    public function usageSessions(): HasMany
    {
        return $this->hasMany(UsageSession::class);
    }

    public function normalizedRole(): string
    {
        return $this->role === 'student' ? 'participant' : ($this->role ?: 'participant');
    }

    public function isAdmin(): bool
    {
        return $this->normalizedRole() === 'admin';
    }

    public function isDirector(): bool
    {
        return $this->normalizedRole() === 'director';
    }

    public function isInstructor(): bool
    {
        return $this->normalizedRole() === 'instructor';
    }

    public function isParticipant(): bool
    {
        return $this->normalizedRole() === 'participant';
    }

    public function permissionSlugs(): array
    {
        if ($this->isAdmin()) {
            return Permission::pluck('slug')->all();
        }

        $roleObj = $this->relationLoaded('roleModel') && $this->roleModel
            ? $this->roleModel
            : Role::with('permissions')->where('slug', $this->normalizedRole())->first();

        if (!$roleObj) {
            return [];
        }

        return $roleObj->permissions->pluck('slug')->all();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return in_array($permissionSlug, $this->permissionSlugs(), true);
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name)) ?: [];
        $i = mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1);
        return mb_strtoupper($i ?: '?');
    }
}
