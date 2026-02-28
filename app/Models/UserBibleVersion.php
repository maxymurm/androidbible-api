<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBibleVersion extends Model
{
    protected $fillable = [
        'user_id', 'bible_version_id', 'is_downloaded',
        'is_favorite', 'sort_order', 'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_downloaded' => 'boolean',
            'is_favorite' => 'boolean',
            'last_read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function bibleVersion(): BelongsTo { return $this->belongsTo(BibleVersion::class); }
}
