<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'title', 'description', 'duration_days', 'language',
        'category', 'thumbnail', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function days(): HasMany { return $this->hasMany(ReadingPlanDay::class); }
    public function progress(): HasMany { return $this->hasMany(ReadingPlanProgress::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function getRouteKeyName(): string { return 'slug'; }
}
