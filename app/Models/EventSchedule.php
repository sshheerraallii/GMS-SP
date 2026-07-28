<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'date',
        'start_time',
        'end_time',
        'total_hours',
    ];

    /* ================= RELATIONSHIPS ================= */

    // Each schedule belongs to an Event
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
