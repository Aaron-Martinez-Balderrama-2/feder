<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    protected $primaryKey = 'page_path';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'page_path',
        'page_title',
        'hits_count',
        'last_visited_at',
    ];

    protected function casts(): array
    {
        return [
            'hits_count' => 'integer',
            'last_visited_at' => 'datetime',
        ];
    }
}
