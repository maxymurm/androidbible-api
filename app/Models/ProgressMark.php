<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProgressMark extends Model
{
    protected $fillable = [
        'gid', 'user_id', 'preset_id', 'ari', 'bible_version_slug', 'caption',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProgressMark $mark) {
            if (empty($mark->gid)) {
                $mark->gid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function history(): HasMany { return $this->hasMany(ProgressMarkHistory::class); }
}
