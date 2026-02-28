<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Marker extends Model
{
    use HasFactory, SoftDeletes;

    const KIND_BOOKMARK = 0;
    const KIND_NOTE = 1;
    const KIND_HIGHLIGHT = 2;

    const KINDS = [
        self::KIND_BOOKMARK => 'bookmark',
        self::KIND_NOTE => 'note',
        self::KIND_HIGHLIGHT => 'highlight',
    ];

    protected $fillable = [
        'gid', 'user_id', 'kind', 'ari', 'ari_end', 'bible_version_slug',
        'caption', 'highlight_color', 'verse_count', 'marker_date',
    ];

    protected function casts(): array
    {
        return [
            'marker_date' => 'datetime',
            'ari' => 'integer',
            'ari_end' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Marker $marker) {
            if (empty($marker->gid)) {
                $marker->gid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'marker_label')->withTimestamps();
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeBookmarks($query) { return $query->where('kind', self::KIND_BOOKMARK); }
    public function scopeNotes($query) { return $query->where('kind', self::KIND_NOTE); }
    public function scopeHighlights($query) { return $query->where('kind', self::KIND_HIGHLIGHT); }

    public function scopeByAri($query, int $ari) { return $query->where('ari', $ari); }
    public function scopeForVersion($query, string $slug) { return $query->where('bible_version_slug', $slug); }

    // ── Helpers ──────────────────────────────────────────────────────

    public function getKindNameAttribute(): string
    {
        return self::KINDS[$this->kind] ?? 'unknown';
    }

    public function getDecodedAriAttribute(): array
    {
        return Verse::decodeAri($this->ari);
    }
}
