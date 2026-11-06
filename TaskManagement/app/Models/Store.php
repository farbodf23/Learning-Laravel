<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(StoreMembership::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_memberships')
            ->withPivot(['id', 'role'])
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function membershipFor(User $user): ?StoreMembership
    {
        return $this->memberships()
            ->where('user_id', $user->id)
            ->first();
    }
}
