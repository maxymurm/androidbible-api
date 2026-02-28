<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncState extends Model
{
    protected $fillable = [
        'user_id', 'device_id', 'last_synced_version', 'last_sync_at',
    ];

    protected function casts(): array
    {
        return ['last_sync_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
