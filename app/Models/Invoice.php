<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'event_id',
        'invoice_number',
        'status',
        'issued_at',
        'paid_at',
        'voided_at',
        'event_name_snapshot',
        'client_type_snapshot',
        'charge_rate_snapshot',
        'vat_rate',
        'supplier_name_snapshot',
        'payment_notes',
        'total_hours',
        'subtotal',
        'vat_amount',
        'total',
        'pdf_path',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
        'vat_rate' => 'decimal:4',
        'total_hours' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'charge_rate_snapshot' => 'decimal:2',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('line_no');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
