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
        'site_postcode',
        'site_name',
        'site_address',
        'shift_no',
        'cancelled_at',
    ];

    protected $casts = [
        'date' => 'date',
        'break_hours' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Only non-cancelled shifts. Cancelled shifts are omitted from all
     * billing, pay sheets, timesheets and reports (they count as zero).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at');
    }

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