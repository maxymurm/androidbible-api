<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Verse extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'bible_version_id', 'book_id', 'ari',
        'chapter_num', 'verse_num', 'text', 'text_formatted',
    ];

    public function bibleVersion(): BelongsTo { return $this->belongsTo(BibleVersion::class); }
    public function book(): BelongsTo { return $this->belongsTo(Book::class); }

    // ── ARI Helpers ──────────────────────────────────────────────────

    /**
     * Encode an ARI from book, chapter, verse.
     * ARI = (bookId << 16) | (chapter << 8) | verse
     */
    public static function encodeAri(int $bookId, int $chapter, int $verse): int
    {
        return ($bookId << 16) | ($chapter << 8) | $verse;
    }

    /**
     * Decode an ARI to [bookId, chapter, verse].
     */
    public static function decodeAri(int $ari): array
    {
        return [
            'book_id' => ($ari >> 16) & 0xFF,
            'chapter' => ($ari >> 8) & 0xFF,
            'verse' => $ari & 0xFF,
        ];
    }

    // ── Scout Search ─────────────────────────────────────────────────

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'bible_version_id' => $this->bible_version_id,
            'ari' => $this->ari,
            'chapter_num' => $this->chapter_num,
            'verse_num' => $this->verse_num,
            'text' => $this->text,
        ];
    }

    public function searchableAs(): string
    {
        return 'verses';
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeByVersion($query, $versionId) { return $query->where('bible_version_id', $versionId); }
    public function scopeByChapter($query, int $bookId, int $chapter) {
        return $query->where('book_id', $bookId)->where('chapter_num', $chapter);
    }
    public function scopeByAri($query, int $ari) { return $query->where('ari', $ari); }
}
