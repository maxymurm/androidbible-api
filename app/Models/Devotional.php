<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devotional extends Model
{
    protected $fillable = [
        'slug', 'title', 'body', 'publish_date', 'author',
        'ari_references', 'language', 'thumbnail', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
            'ari_references' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished($query) { return $query->where('is_published', true); }
    public function scopeToday($query) { return $query->where('publish_date', now()->toDateString()); }
    public function getRouteKeyName(): string { return 'slug'; }
}
