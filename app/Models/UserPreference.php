<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id', 'active_bible_version_slug', 'active_book_id',
        'active_chapter', 'active_verse', 'font_size', 'font_family',
        'line_spacing', 'night_mode', 'theme', 'continuous_scroll',
        'show_verse_numbers', 'show_red_letters', 'extra',
    ];

    protected function casts(): array
    {
        return [
            'font_size' => 'float',
            'line_spacing' => 'float',
            'night_mode' => 'boolean',
            'continuous_scroll' => 'boolean',
            'show_verse_numbers' => 'boolean',
            'show_red_letters' => 'boolean',
            'extra' => 'array',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
