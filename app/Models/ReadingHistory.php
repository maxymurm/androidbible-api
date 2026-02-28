<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingHistory extends Model
{
    protected $table = 'reading_history';

    protected $fillable = [
        'user_id', 'bible_version_id', 'book_id',
        'chapter_num', 'ari', 'scroll_position',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function bibleVersion(): BelongsTo { return $this->belongsTo(BibleVersion::class); }
}
