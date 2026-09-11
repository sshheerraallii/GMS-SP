<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * V3-P4: tracking row for a guard's payment on a single event.
 *
 * Deliberately holds no money: hours, rate and amount are recomputed
 * live from event_shifts every time the sheet is rendered or exported.
 */
class EventGuardPayment extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'event_id',
        'guard_id',
        'paid_by',
        'paid_on',
        'remarks',
    ];

    protected $casts = [
        'paid_on' => 'date',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guard(): BelongsTo
    {
        return $this->belongsTo(SecurityGuard::class, 'guard_id');
    }
}
