<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardInvoiceLine extends Model
{
    protected $fillable = [
        'guard_invoice_id',
        'line_no',
        'service_date',
        'description',
        'shift_start',
        'shift_end',
        'hours',
        'rate',
        'amount',
    ];

    protected $casts = [
        'service_date' => 'date',
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function guardInvoice(): BelongsTo
    {
        return $this->belongsTo(GuardInvoice::class);
    }
}
