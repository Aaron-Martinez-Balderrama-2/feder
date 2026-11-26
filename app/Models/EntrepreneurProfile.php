<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntrepreneurProfile extends Model
{
    protected $fillable = [
        'user_id',
        'social_organization_id',
        'business_name',
        'sector',
        'business_stage',
        'employees_count',
        'monthly_income_range',
        'municipality',
        'vulnerability_group',
        'social_impact_notes',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'employees_count' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function socialOrganization(): BelongsTo
    {
        return $this->belongsTo(SocialOrganization::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
