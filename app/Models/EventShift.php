<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SecurityGuard;
use App\Models\Event;

class EventShift extends Model
{
    protected $table = 'event_shifts';

    protected $fillable = [
        'event_id',
        'guard_id',
        'date',
        'start_time',
        'end_time',
        'shift_no',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Each shift belongs to an event
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Each shift belongs to a security guard
     */
    public function securityGuard(): BelongsTo
    {
        return $this->belongsTo(SecurityGuard::class, 'guard_id');
    }
}
