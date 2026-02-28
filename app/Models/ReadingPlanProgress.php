<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingPlanProgress extends Model
{
    protected $table = 'reading_plan_progress';

    protected $fillable = [
        'user_id', 'reading_plan_id', 'start_date', 'current_day',
        'completed_days', 'status', 'bible_version_slug', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'completed_days' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function readingPlan(): BelongsTo { return $this->belongsTo(ReadingPlan::class); }

    public function markDayComplete(int $day): void
    {
        $completed = $this->completed_days ?? [];
        if (!in_array($day, $completed)) {
            $completed[] = $day;
            sort($completed);
            $this->completed_days = $completed;
            $this->current_day = max($completed) + 1;

            if (count($completed) >= $this->readingPlan->duration_days) {
                $this->status = 'completed';
                $this->completed_at = now();
            }

            $this->save();
        }
    }
}
