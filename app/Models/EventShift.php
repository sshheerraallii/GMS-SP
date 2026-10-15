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
        'pay_rate',
        'charge_rate',
        'expense',
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
        'pay_rate' => 'decimal:2',
        'charge_rate' => 'decimal:2',
        'expense' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    /**
     * V5-P3: the rate this shift is valued at.
     *
     *   shift override (if set)  ->  guard category rate  ->  event base rate  ->  0
     *
     * The single entry point for per-shift valuation. Every consumer must
     * call this (never read pay_rate / charge_rate directly) so the
     * override logic exists in one place only. $kind is 'pay' or 'charge'.
     * With no override set this is exactly Event::rateFor(), so events
     * that never use overrides value identically to before.
     */
    public function resolvedRate(Event $event, ?string $category, string $kind): float
    {
        $override = strtolower(trim($kind)) === 'charge' ? $this->charge_rate : $this->pay_rate;

        if ($override !== null && $override !== '') {
            return (float) $override;
        }

        return $event->rateFor($category, $kind);
    }

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