<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounterAssignment extends Model
{
    protected $fillable = ['user_id', 'counter_id', 'start_at', 'end_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }
}
