<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'user_id', 'device_id', 'platform', 'push_token',
        'app_version', 'os_version', 'device_name', 'last_active_at',
    ];

    protected function casts(): array
    {
        return ['last_active_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
