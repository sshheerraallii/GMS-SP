<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuardInvoice extends Model
{
    protected $fillable = [
        'event_id',
        'guard_id',
        'invoice_number',
        'status',
        'issued_at',
        'paid_at',
        'voided_at',
        'event_name_snapshot',
        'pay_rate_snapshot',
        'total_hours',
        'subtotal',
        'total',
        'pdf_path',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
        'pay_rate_snapshot' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function securityGuard(): BelongsTo
{
    return $this->belongsTo(SecurityGuard::class, 'guard_id');
}


    public function lines(): HasMany
    {
        return $this->hasMany(GuardInvoiceLine::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }
}
