<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pericope extends Model
{
    protected $fillable = ['bible_version_id', 'ari', 'title', 'sort_order'];

    public function bibleVersion(): BelongsTo { return $this->belongsTo(BibleVersion::class); }
}
