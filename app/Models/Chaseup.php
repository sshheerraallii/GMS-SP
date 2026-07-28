<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chaseup extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'event_shift_id',
                'event_id',      // added
        'guard_id',      // added
        'date',
        'reminder',
        'all_ok',
        'all_ok_response',
        'on_way',
        'on_way_response',
        'reaching_time',
        'book_on',
        'comments',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function eventShift(): BelongsTo
    {
        return $this->belongsTo(EventShift::class, 'event_shift_id');
    }
}