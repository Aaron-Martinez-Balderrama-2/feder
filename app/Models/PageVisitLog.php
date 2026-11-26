<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisitLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'page_path',
        'page_title',
        'user_id',
        'ip_address',
        'user_agent',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }
}
