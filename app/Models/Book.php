<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'bible_version_id', 'book_id', 'short_name', 'name',
        'chapter_count', 'verse_count', 'testament', 'sort_order',
    ];

    public function bibleVersion(): BelongsTo { return $this->belongsTo(BibleVersion::class); }
    public function verses(): HasMany { return $this->hasMany(Verse::class); }

    public function scopeOldTestament($query) { return $query->where('testament', 'OT'); }
    public function scopeNewTestament($query) { return $query->where('testament', 'NT'); }
}
