<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingPlanDay extends Model
{
    protected $fillable = [
        'reading_plan_id', 'day_number', 'title', 'description', 'ari_ranges',
    ];

    protected function casts(): array
    {
        return ['ari_ranges' => 'array'];
    }

    public function readingPlan(): BelongsTo { return $this->belongsTo(ReadingPlan::class); }
}
