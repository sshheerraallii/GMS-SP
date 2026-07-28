<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SecurityGuard;
use App\Models\Event;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventShift extends Model
{
    protected $table = 'event_shifts';

    protected $fillable = [
        'event_id',
        'guard_id',
        'date',
        'start_time',
        'end_time',
        'break_hours',
        'location',
        'shift_no',
    ];

    protected $casts = [
        'date' => 'date',
        'break_hours' => 'decimal:2',
    ];

    /**
     * Each shift belongs to an event
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
    
    public function chaseup(): HasOne
{
    return $this->hasOne(\App\Models\Chaseup::class, 'event_shift_id');
}

    /**
     * Each shift belongs to a security guard
     */
    public function securityGuard(): BelongsTo
    {
        return $this->belongsTo(SecurityGuard::class, 'guard_id');
    }
}