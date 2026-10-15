<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAssignmentSlot extends Model
{
    protected $table = 'event_assignment_slots';

    protected $fillable = [
        'event_id',
        'date',
        'slot_no',
        'guard_id',
        'temp_phone',
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
    ];

    protected $casts = [
        'date' => 'date',
        'break_hours' => 'decimal:2',
        'pay_rate' => 'decimal:2',
        'charge_rate' => 'decimal:2',
        'expense' => 'decimal:2',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function securityGuard(): BelongsTo
    {
        return $this->belongsTo(SecurityGuard::class, 'guard_id');
    }
}