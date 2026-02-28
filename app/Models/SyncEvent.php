<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncEvent extends Model
{
    protected $fillable = [
        'event_id', 'user_id', 'entity_type', 'entity_gid',
        'action', 'payload', 'changed_fields', 'device_id',
        'version', 'event_timestamp',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'changed_fields' => 'array',
            'event_timestamp' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function scopeAfterVersion($query, int $version)
    {
        return $query->where('version', '>', $version);
    }

    public function scopeForEntity($query, string $type, string $gid)
    {
        return $query->where('entity_type', $type)->where('entity_gid', $gid);
    }
}
