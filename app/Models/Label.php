<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Label extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'gid', 'user_id', 'title', 'background_color', 'sort_order',
    ];

    protected static function booted(): void
    {
        static::creating(function (Label $label) {
            if (empty($label->gid)) {
                $label->gid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function markers(): BelongsToMany
    {
        return $this->belongsToMany(Marker::class, 'marker_label')->withTimestamps();
    }
}
