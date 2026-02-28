<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressMarkHistory extends Model
{
    protected $table = 'progress_mark_history';

    protected $fillable = [
        'user_id', 'progress_mark_id', 'ari', 'bible_version_slug', 'progress_date',
    ];

    protected function casts(): array
    {
        return ['progress_date' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function progressMark(): BelongsTo { return $this->belongsTo(ProgressMark::class); }
}
