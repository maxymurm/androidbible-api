<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SongBook extends Model
{
    protected $fillable = [
        'slug', 'title', 'description', 'language', 'publisher', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function songs(): HasMany { return $this->hasMany(Song::class); }
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function getRouteKeyName(): string { return 'slug'; }
}
