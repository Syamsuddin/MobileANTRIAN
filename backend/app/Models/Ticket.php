<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const STATUS_WAITING = 'waiting';

    public const STATUS_CALLED = 'called';

    public const STATUS_SERVING = 'serving';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_DONE = 'done';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const ACTIVE_STATUSES = [self::STATUS_CALLED, self::STATUS_SERVING];

    protected $fillable = [
        'ticket_no',
        'service_id',
        'ticket_date',
        'status',
        'called_at',
        'started_at',
        'skipped_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'ticket_date' => 'date',
            'called_at' => 'datetime',
            'started_at' => 'datetime',
            'skipped_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function queueCalls(): HasMany
    {
        return $this->hasMany(QueueCall::class);
    }
}
