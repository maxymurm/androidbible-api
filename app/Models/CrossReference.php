<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrossReference extends Model
{
    protected $fillable = ['bible_version_id', 'from_ari', 'to_ari', 'to_ari_end'];

    public function bibleVersion(): BelongsTo { return $this->belongsTo(BibleVersion::class); }
}
