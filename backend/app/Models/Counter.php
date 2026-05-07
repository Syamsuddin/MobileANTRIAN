<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Counter extends Model
{
    protected $fillable = ['code', 'name', 'location', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'counter_services')->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CounterAssignment::class);
    }
}
