<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibleVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'short_name', 'name', 'language', 'language_name',
        'description', 'publisher', 'copyright', 'year',
        'has_old_testament', 'has_new_testament', 'has_apocrypha',
        'is_active', 'sort_order', 'verse_count', 'text_direction',
    ];

    protected function casts(): array
    {
        return [
            'has_old_testament' => 'boolean',
            'has_new_testament' => 'boolean',
            'has_apocrypha' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function books(): HasMany { return $this->hasMany(Book::class, 'bible_version_id'); }
    public function verses(): HasMany { return $this->hasMany(Verse::class, 'bible_version_id'); }
    public function pericopes(): HasMany { return $this->hasMany(Pericope::class, 'bible_version_id'); }
    public function crossReferences(): HasMany { return $this->hasMany(CrossReference::class, 'bible_version_id'); }
    public function footnotes(): HasMany { return $this->hasMany(Footnote::class, 'bible_version_id'); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeByLanguage($query, string $language) { return $query->where('language', $language); }

    public function getRouteKeyName(): string { return 'slug'; }
}
