<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** V4-P3: company-wide overhead, not tied to any event or client. */
class GeneralExpense extends Model
{
    protected $fillable = [
        'label',
        'amount',
        'date',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];
}
