<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Song extends Model
{
    protected $fillable = [
        'song_book_id', 'number', 'title', 'title_original',
        'author', 'tune', 'key_signature', 'lyrics', 'lyrics_formatted',
        'ari_references', 'audio_url',
    ];

    protected function casts(): array
    {
        return ['ari_references' => 'array'];
    }

    public function songBook(): BelongsTo { return $this->belongsTo(SongBook::class); }
}
