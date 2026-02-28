<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchHistory extends Model
{
    protected $table = 'search_history';

    protected $fillable = ['user_id', 'query', 'version_slug', 'results_count'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
