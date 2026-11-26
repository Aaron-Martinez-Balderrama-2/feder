<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialOrganization extends Model
{
    protected $fillable = [
        'name',
        'organization_type',
        'municipality',
        'department',
        'contact_name',
        'contact_phone',
        'agreement_status',
        'beneficiaries_target',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'beneficiaries_target' => 'integer',
        ];
    }

    public function entrepreneurProfiles(): HasMany
    {
        return $this->hasMany(EntrepreneurProfile::class);
    }
}
