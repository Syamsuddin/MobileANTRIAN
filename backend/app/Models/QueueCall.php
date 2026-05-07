<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueCall extends Model
{
    public const EVENT_CALL = 'call';

    public const EVENT_RECALL = 'recall';

    public const EVENT_SKIP = 'skip';

    public const EVENT_DONE = 'done';

    protected $fillable = [
        'ticket_id',
        'counter_id',
        'operator_id',
        'call_no',
        'event_type',
        'called_at',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'called_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
