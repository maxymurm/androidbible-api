<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Footnote extends Model
{
    protected $fillable = ['bible_version_id', 'ari', 'content', 'field'];

    public function bibleVersion(): BelongsTo { return $this->belongsTo(BibleVersion::class); }
}
