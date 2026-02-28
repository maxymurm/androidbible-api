<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'locale',
        'timezone', 'provider', 'provider_id', 'bio', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function devices() { return $this->hasMany(Device::class); }
    public function markers() { return $this->hasMany(Marker::class); }
    public function bookmarks() { return $this->hasMany(Marker::class)->where('kind', Marker::KIND_BOOKMARK); }
    public function notes() { return $this->hasMany(Marker::class)->where('kind', Marker::KIND_NOTE); }
    public function highlights() { return $this->hasMany(Marker::class)->where('kind', Marker::KIND_HIGHLIGHT); }
    public function labels() { return $this->hasMany(Label::class); }
    public function progressMarks() { return $this->hasMany(ProgressMark::class); }
    public function readingPlanProgress() { return $this->hasMany(ReadingPlanProgress::class); }
    public function preferences() { return $this->hasOne(UserPreference::class); }
    public function syncEvents() { return $this->hasMany(SyncEvent::class); }
    public function syncStates() { return $this->hasMany(SyncState::class); }

    public function getNextSyncVersion(): int
    {
        return ($this->syncEvents()->max('version') ?? 0) + 1;
    }
}
