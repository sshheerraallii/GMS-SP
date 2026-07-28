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
        'start_time',
        'end_time',
        'break_hours',
        'location',
    ];

    protected $casts = [
        'date' => 'date',
        'break_hours' => 'decimal:2',
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